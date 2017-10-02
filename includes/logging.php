<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Formatter\LineFormatter;

# Create a global variable to access all logging
#global $log;

# Create a logger instance
$log = new Logger('POR2WooCommerce');

# Create the proper formatter
$formatter = new LineFormatter();
$formatter->allowInlineLineBreaks();

# If email logging is enabled
if($EmailLog)
{
    # Create SwiftMailer Instance
    $sMailer = new Swift_Mailer((new Swift_SmtpTransport($smtpHost, $smtpPort, $smtpEncyption))->setUsername($smtpUser)->setPassword($smtpPassword));
    
    $message = (new Swift_Message($smtpSubject))
        ->setFrom(["$smtpFromEmail" => "$smtpFromName"])
#  ->setTo(['receiver@domain.org', 'other@domain.org' => 'A name'])
        ->setTo(["$smtpDestEmail" => "$smtpDestName"]);

    # Create the stream for email
    $emailStream = new SwiftMailerHandler($sMailer, $message, Logger::DEBUG);

    $emailStream = new BufferHandler($emailStream);

    # Set text formatter
    $emailStream->setFormatter($formatter);

    # Enable stream in logging
    $log->pushHandler($emailStream);
}

# If file logging is enabled
if($FileLog)
{
    # Create the stream for file
    $fileStream = new StreamHandler($logFileDir, Logger::DEBUG);

    # Set text formatter
    $fileStream->setFormatter($formatter);

    # Enable stream in logging
    $log->pushHandler($fileStream);
}

# If console logging is enabled
if($ConsoleLog)
{
    # Create the stream for console
    $consoleStream = new FilterHandler(new StreamHandler('php://stdout', Logger::DEBUG), Logger::DEBUG);

    # Set text formatter
    $consoleStream->setFormatter($formatter);

    # Enable stream in logging
    $log->pushHandler($consoleStream);
}

# If syslog logging is enabled
if($SyslogLog)
{
    # Create the stream for email
    $socketHandler = new SocketHandler("$syslogProto://$syslogIP:$syslogPort");

    # Set text formatter
    $socketHandler->setFormatter($formatter);

    # Enable stream in logging
    $log->pushHandler($socketHandler);
}
