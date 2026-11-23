<?php

/**
 * This file tries to simplify knowing if user is logged in.
 *
 *
 */

namespace Auth;

class IsLoggedIn
{
    private int $who_is_logged_in = 0;

    private string $loggedInUsername = 'YUNOset?'; // default value, should be overwritten if user is logged in
    public function __construct(
        private readonly \PDO $di_pdo,
        private readonly \Config $di_config,
    ) {
    }

    public function checkLogin(\Mlaphp\Request $mla_request): void
    {
        $found_user_id = 0;
        // Request values are kept only when they are strings; anything else
        // counts as missing.
        $cookie = $mla_request->cookie[$this->di_config->cookie_name] ?? '';
        $cookie = is_string($cookie) ? $cookie : '';
        $username = $mla_request->post['username'] ?? '';
        $username = is_string($username) ? $username : '';
        $password = $mla_request->post['pass'] ?? '';
        $password = is_string($password) ? $password : '';
        if (!empty($cookie)) {
            $found_user_id = $this->getUserIdForCookieInDatabase(
                cookie: $cookie,
                ip_address: self::serverString('REMOTE_ADDR'),
                user_agent: self::serverString('HTTP_USER_AGENT')
            );
            if (empty($found_user_id)) {
                $this->killCookie();
                $this->who_is_logged_in = 0;
            } else {
                $this->who_is_logged_in = $found_user_id;
            }
        } elseif (!empty($username) && !empty($password)) {
            $found_user_id = $this->checkPHPHashedPassword($username, $password);
            if (empty($found_user_id)) {
                $this->killCookie();        // bad login, so kill any cookie
                $this->who_is_logged_in = 0;
            } else {
                // Fresh privilege level → fresh session id (fixation guard): a
                // pre-set session id must not survive the authentication boundary.
                session_regenerate_id(true);
                $this->setAutoLoginCookie($found_user_id);
                $this->who_is_logged_in = $found_user_id;
            }
        }
        // set the session variable for username
        $this->setUsernameOfLoggedInID($this->who_is_logged_in);
    }

    private function setUsernameOfLoggedInID(int $user_id): void
    {
        if ($user_id <= 0) {
            return;
        }
        // set the session variable for username
        $stmt = $this->di_pdo->prepare("SELECT `username` FROM `users` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$user_id]);
        /** @var list<array{username: string}> $result users.username is VARCHAR NOT NULL */
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            $this->loggedInUsername = $result[0]['username'];
        }
    }

    /**
     * A $_SERVER entry as a string; '' when it is missing or not a string.
     */
    private static function serverString(string $key): string
    {
        $value = $_SERVER[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    public function getLoggedInUsername(): string
    {
        return $this->loggedInUsername;
    }
    private function setAutoLoginCookie(int $user_id): void
    {
        $cookie = \Utilities::randomString(32);

        $record = [
            'user_id' => $user_id,
            // Store only the SHA-256 of the token: a leaked DB dump/backup must
            // not contain ready-to-use session tokens. The browser holds the
            // plaintext; lookups hash the presented value (see
            // getUserIdForCookieInDatabase).
            'cookie' => hash('sha256', $cookie),
            'last_access' => date(format: "Y-m-d H:i:s"),
            'user_agent_md5' => md5(self::serverString('HTTP_USER_AGENT')),
            'ip_address' => \Auth\IPBin::ipToBinary(ip: self::serverString('REMOTE_ADDR'))
        ];

        // Insert using native PDO
        $stmt = $this->di_pdo->prepare("INSERT INTO `cookies` (`user_id`, `cookie`, `last_access`, `user_agent_md5`, `ip_address`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array_values($record));

        $cookie_options = [
            'expires' => time() + $this->di_config->cookie_lifetime, // 30 days
            'path' => '/',
            'domain' => $this->di_config->domain_name,
            'samesite' => 'Strict' // None || Lax  || Strict
        ];
        setcookie($this->di_config->cookie_name, $cookie, $cookie_options);
    }



    /**
     * @param string $username from the login form's POST
     * @return array{}|array{user_id: int, password_hash: string} the user's row, or empty when no user matches
     */
    private function getIDandPHPHashedPasswordForUsername($username): array
    {
        // get password hash
        $stmt = $this->di_pdo->prepare("SELECT `user_id`, `password_hash` FROM `users` WHERE LOWER(`username`) = LOWER(?) LIMIT 1");
        $stmt->execute([$username]);
        /** @var list<array{user_id: int, password_hash: string}> $result */
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            return $result[0];
        } else {
            return [];
        }
    }
    /**
     * Looks up hashed password for username, and checks it against the password provided
     * @param string $username from the login form's POST
     * @param string $password from the login form's POST
     * @return int
     */
    private function checkPHPHashedPassword($username, $password): int
    {
        // get password hash
        $user_id_and_hash_array = $this->getIDandPHPHashedPasswordForUsername($username);
        if (!empty($user_id_and_hash_array)) {
            $hashed_password = $user_id_and_hash_array['password_hash'];
            // check it
            if (password_verify($password, $hashed_password)) {
                // password is correct, so this user_id has logged in properly
                $user_id = $user_id_and_hash_array['user_id'];
                // return user_id
                return $user_id;
            }
        } else {
            return 0;
        }

        return 0;
    }


    private function getUserIdForCookieInDatabase(
        string $cookie,
        string $ip_address,
        string $user_agent
    ): int {
        $varbinary_ip = \Auth\IPBin::ipToBinary($ip_address);
        $stmt = $this->di_pdo->prepare("SELECT `user_id` FROM `cookies` WHERE `cookie` = ? AND `ip_address` = ? AND `user_agent_md5` = ? LIMIT 1");
        $stmt->execute([hash('sha256', $cookie), $varbinary_ip, md5($user_agent)]);
        /** @var list<array{user_id: int}> $result cookies.user_id is INT UNSIGNED NOT NULL */
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            return $result[0]['user_id'];
        } else {
            return 0;
        }
    }
    public function isLoggedIn(): bool
    {
        return $this->who_is_logged_in > 0;
    }

    public function loggedInID(): int
    {
        return $this->who_is_logged_in;
    }


    public function logout(): void
    {
        // Revoke the token server-side, not just in the browser — otherwise a
        // captured cookie value keeps working for its full lifetime after the
        // user has "logged out".
        $presented = $_COOKIE[$this->di_config->cookie_name] ?? '';
        if (is_string($presented) && $presented !== '') {
            $stmt = $this->di_pdo->prepare("DELETE FROM `cookies` WHERE `cookie` = ?");
            $stmt->execute([hash('sha256', $presented)]);
        }

        $this->who_is_logged_in = 0;
        $this->killCookie();
        session_destroy();
        session_start();
        session_regenerate_id();
    }

    private function killCookie(): void
    {
        $cookie_options = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $this->di_config->domain_name,
            'samesite' => 'Strict' // None || Lax  || Strict
        ];
        setcookie($this->di_config->cookie_name, '', $cookie_options);
        $this->who_is_logged_in = 0;
    }
}
