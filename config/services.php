<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],


    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'mailjet' => [
        'key' => env('MAILJET_APIKEY'),
        'secret' => env('MAILJET_APISECRET'),
        'transactional' => [
            'call' => true,
            'options' => [
                'url' => 'api.mailjet.com',
                'version' => 'v3.1',
                'call' => true,
                'secured' => true
            ]
        ],
        'common' => [
            'call' => true,
            'options' => [
                'url' => 'api.mailjet.com',
                'version' => 'v3',
                'call' => true,
                'secured' => true
            ]
        ],
        'v4' => [
            'call' => true,
            'options' => [
                'url' => 'api.mailjet.com',
                'version' => 'v4',
                'call' => true,
                'secured' => true
            ]
        ],
    ],

    'email' => [
        'subject' => env('EMAIL_SUBJECT'),
        'subjectTicket' => env('EMAIL_TICKET_SUBJECT'),
        'subjectTicketCancel' => env('EMAIL_TICKET_CANCEL_SUBJECT')
    ],

    'sms' => [
        'otpservice' => env('OTP_SERVICE','textLocal'),
        'otp_service_enabled' => true,
        'textlocal' => [
            'key' => env('SMS_TEXTLOCAL_KEY'),
            'url_send' => env('TXTLOCAL_SEND_SMS_URL'),
            'url_status' => env('TXTLOCAL_STATUS_SMS_URL'),
            'message' => env('SMS_TEMPLATE'),
            'msgAgent' => env('SMS_TEMPLATE_AGENT'),
            'msgTicket' => env('SMS_TKT_TEMPLATE'),
            'appDownload' => env('APP_DOWNLOAD_TEMPLATE'),            
            'msgTicketCMO' => env('SMS_TKT_TEMPLATE_CMO'),
            'cancelTicket' => env('CANCEL_TKT_TEMPLATE'),
            'cancelTicketCMO' => env('CANCEL_TKT_TEMPLATE_CMO'),
            'dolphinTkt' => env('DOLPHIN_TKT_TEMPLATE'),            
            'cancelTicketOTP' => env('OTP_TICKET_CANCEL'),
            'senderid' => env('SENDER_ID'),
            
        ],
        'indiaHub' => [
            'key' => env('SMS_INDIA_HUB_KEY'),
            'url_send' => env('TEXT_SMS_INDIA_HUB_URL'),
            'url_msg' => env('TEXT_LOCAL_MESSAGE_URL'),
            'message' => env('SMS_TEMPLATE'),
            'msgAgent' => env('SMS_TEMPLATE_AGENT'),
            'msgTicket' => env('SMS_TKT_TEMPLATE'),
            'msgTicketCMO' => env('SMS_TKT_TEMPLATE_CMO'),
            'cancelTicket' => env('CANCEL_TKT_TEMPLATE'),
            'cancelTicketCMO' => env('CANCEL_TKT_TEMPLATE_CMO'),
            'dolphinTkt' => env('DOLPHIN_TKT_TEMPLATE'), 
            'cancelTicketOTP' => env('OTP_TICKET_CANCEL'),
            'senderid' => env('SENDER_ID'),
        ]
    ],
    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET')
    ],

];
