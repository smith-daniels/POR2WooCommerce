<?php

# Define ABSPATH as this file's directory
if(!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__).'/');

# Define folder name for SQL
if(!defined('SQLPATH'))
    define('SQLPATH', 'SQL');

# Define folder name for Exceptions
if(!defined('EXCPATH'))
    define('EXCPATH', 'Exceptions');

# Define folder name for hashing
if(!defined('HASHPATH'))
    define('HASHPATH', 'Hashing');

# Define include folder
if(!defined('INCLPATH'))
    define('INCLPATH', 'includes/');

# Import and composer packages needed
require_once ABSPATH.'/vendor/autoload.php';

# Require config file
require_once ABSPATH.'config.php';

# Require logging 
require_once ABSPATH.INCLPATH.'logging.php';

# Require SQL files
require_once ABSPATH.INCLPATH.SQLPATH.'/SQLHash.php';
require_once ABSPATH.INCLPATH.SQLPATH.'/SQL.php';

# Require exceptions
require_once ABSPATH.INCLPATH.EXCPATH.'/PostException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/GetException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/DeleteException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/TermExists.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/CommerceConnectException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/SQLException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/ItemFileException.php';
require_once ABSPATH.INCLPATH.EXCPATH.'/SQLHashException.php';

# Require Hashing
require_once ABSPATH.INCLPATH.HASHPATH.'/Hashing.php';
require_once ABSPATH.INCLPATH.HASHPATH.'/CheckHashes.php';

# Require imageClass
require_once ABSPATH.INCLPATH.'imageClass.php';

# Require ItemFile
require_once ABSPATH.INCLPATH.'ItemFile.php';

# Require CommerceConnect
require_once ABSPATH.INCLPATH.'CommerceConnect.php';
