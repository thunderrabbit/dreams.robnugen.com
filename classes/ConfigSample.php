<?php

class Config
{
    public string $domain_name = 'dreams.robnugen.com';  // used for cookies
    public string $cookie_name = 'dreamlogin'; // used for cookies
    public int $cookie_lifetime = 60 * 60 * 24 * 30; // 30 days
    public string $app_path = '/home/barefoot_rob/dreams.robnugen.com';
    public string $post_path_journal = '/home/barefoot_rob/robnugen.com/journal/journal';
    public string $dreams_import_pointer_file = '/home/barefoot_rob/dreams_import_pointer.txt';
    public string $dreams_failed_files = '/home/barefoot_rob/dreams_failed_files.txt';

    public string $dbHost = "localhost";
    public string $dbUser = "";
    public string $dbPass = "";
    public string $dbName = "";
}
