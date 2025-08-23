<?php

class Config {

    public $domain_name = 'dreams.robnugen.com';  // used for cookies
    public $cookie_name = 'dreamlogin'; // used for cookies
    public $cookie_lifetime = 60 * 60 * 24 * 30; // 30 days
    public $app_path = '/home/barefoot_rob/dreams.robnugen.com';
    public $post_path_journal = '/home/barefoot_rob/robnugen.com/journal/journal';
    public $dreams_import_pointer_file = '/home/barefoot_rob/dreams_import_pointer.txt';
    public $dreams_failed_files = '/home/barefoot_rob/dreams_failed_files.txt';

    public $dbHost = "localhost";
    public $dbUser = "";
    public $dbPass = "";
    public $dbName = "";
}
