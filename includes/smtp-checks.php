<?php

namespace Mail_Control;

/**
 * Sanitizes smtp host ( ip4, ip6, or domain name)
 *
 * @param      string  $string  The string
 *
 * @return     string  sanitized smtp host
 */
function sanitize_smtp_host($string)
{
    if ($ip4 = filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ip4;
    } elseif ($ip6 = filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $ip6;
    } elseif ($domain = filter_var($string, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        return $domain;
    }
    return '';
}


/**
 * Initializes the test email mode.
 */
function init_test_email_mode()
{
    // disable backgroud sending.
    add_filter('mc_disable_email_queue', '__return_true');
    // Init phpmailer SMTPDEBUG.
    add_action('phpmailer_init', function ($phpmailer) {
        $phpmailer->SMTPDebug = true;
    }, 11);
}


/**
 * Tests the presence of the spf record ( and suggests correction if necessary )
 *
 * @param      string  $domain   The domain
 * @param      string  $smtp_host   The smtp host
 * @param      array  $reports  The reports
 */
function test_spf_record(string $domain, string $smtp_host, array $report)
{
    $txt = dns_get_record($domain, DNS_TXT);
    $spf = array_values(array_filter($txt, function ($host) {
        return substr($host['txt'], 0, 5) == 'v=spf';
    }));

    $spf_count = count($spf);
    if ($ip = filter_var($smtp_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $allow = 'ip4:'.$ip;
    } elseif ($ip = filter_var($smtp_host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $allow = 'ip6:'.$ip;
    } elseif ($smtp_host = filter_var($smtp_host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        $allow = 'a:'.$smtp_host;
    } else {
        $report[] = '<p class="notice notice-error">>'. sprintf(__('The smtp server %s doesn\'t seem to be a valid domain name or IP adress', 'mail-control'), $smtp_host).'</p>';
        return [false, $report ];
    }

    $report[] = '<h3>'.__('SPF record verification', 'mail-control').'</h3>';
    $report[] = '<p>'. sprintf(__('The main idea behind the SPF concept is to make sure all applications that send emails for your domain %s are included in your SPF record (their domain name or IP to be precise).', 'mail-control'), $domain).'</p>';

    $spf_suggestion = 'v=spf1 mx '.$allow.' ~all';

    $spf_ok = false;
    if ($spf_count == 0) {
        $report[] = '<p class="notice notice-error">'.sprintf(__('There is no SPF record for you domain %s, some mail providers won\'t allow emails without an SPF record, you should add an spf record to you DNS', 'mail-control'), $domain).'</p>';

        $report[] = '<p class="notice notice-info">';
        $report[] = sprintf(__('We suggest you add this line : %s', 'mail-control'), $spf_suggestion).'<br/>';
        $report[] = sprintf(__('This line will tell receiving mail servers to allow emails coming from your MX servers as well as your smtp host %s', 'mail-control'), $smtp_host);
        $report[] = '</p>';
    } elseif (count($spf) > 1) {
        $report[] = '<p class="notice notice-error">';
        $report[] = __('There seems to be more than one spf record!!', 'mail-control');
        $report[] = __('This can cause your emails to get blocked, you should merge them', 'mail-control');
        $report[] = '</p>';
    } else {
        // $host = dns_get_record( $smtp_host, DNS_A|DNS_CNAME);
        // TODO : check it our host is allowed in spf
        // TODO : check if it's IP is allowed in spf
        $spf_field =  $spf[0]['txt'];
        $record = sprintf(__('Your SPF record : %s', 'mail-control'), $spf_field) ;

        if (strpos($spf_field, $smtp_host)>0) {
            $report[] = '<p class="notice notice-success">'.$record .'<br/>';
            $report[] = sprintf(__('Good! Your SMTP host %s seems to be allowed to send emails for your domain %s', 'mail-control'), $smtp_host, $domain);
            $report[] = '</p>';
            $spf_ok = true;
        } else {
            $report[] = '<p class="notice notice-error">'.$record .'<br/>';
            $report[] = sprintf(__('Aie Aie! Your SMTP host %s doesn\'t seem to be allowed to send emails for your domain %s', 'mail-control'), $smtp_host, $domain);
            $report[] = sprintf(__('Make sure you add "%s" after v=spf1 to your record', 'mail-control'), $allow);
            $report[] = '</p>';
        }
    }
    $spf_wizard = "<a href='https://www.spfwizard.net/' target='_blank'>https://www.spfwizard.net/</a>";
    $spf_info = "<a href='https://fr.wikipedia.org/wiki/Sender_Policy_Framework' target='_blank'>https://fr.wikipedia.org/wiki/Sender_Policy_Framework</a>";
    $report[] = '<p class="notice notice-info"><strong>'.sprintf(__('Please keep in mind that this feature is experimental, you can find more information on SPF here %s, and use this wizard here %s to compose your record', 'mail-control'), $spf_info, $spf_wizard).'</strong></p>';
    return [$spf_ok, $report];
}


/**
 * Tests the presence of the dkim record
 *
 * @param      string  $dkim_host  The DKIM host
 * @param      array   $report    The reports
 *
 * @return     array  ( test result and report )
 */
function test_dkim_record(string $dkim_host, array $report)
{
    // Still experimental
    // Why? dkim is setup in the smtp server side
    // 1 - the receiving server will read the email headers, extract the dkim selector
    // 2 - requests the DKIM public key using this selector
    // 3 - Checks if the headers are correcly signed using this key
    // For our case, we obviously don't know the selector, so we have to ask for it, and probably the user doesn't (or maybe he does if he uses his domain name and DID set it in his DNS, but it is very unlikely he does).
    // If the user is using a gmail address for example (or any other big provider), this feauture is somehow useless.. It's not like we would be able to fix gmail's delivrability..

    // Now, even if we request the DKIM selector, can just test that the record is present
    // To test that the line is actually correct, we'll need to RECEIVE this email and and verify that the dkim is correct
    // For now, we'll just send our dear user to use a more complete external tool : https://www.appmaildev.com/en/dkim
    //
    // If we ever find a (free or very cheap) way to create a temp email, and access it's content via API, maybe then, we could do more than juste checking the presence (and number of records)
    //
    $report[] = '<h3>'.__('DKIM record verification', 'mail-control').'</h3>';
    $report[] = '<p>'. __('DKIM - DomainKeys Identified Mail, is an authentication method that allows the receiver to check that an email claimed to have come from a specific domain was indeed signed by the key advertised in that domain DKIM record', 'mail-control').'</p>';

    if ($dkim_host==='') {
        $report[] = '<p class="notice notice-warning">'. __('You did not provide a dkim selector so we could not test your dkim record', 'mail-control') .'</p>';
        // Consider it did not pass
        return [false, $report];
    }

    $txt = dns_get_record($dkim_host, DNS_TXT);
    $dkim = array_values(array_filter($txt, function ($record) {
        return substr($record['txt'], 0, 6) == 'v=DKIM';
    }));

    $dkim_ok = false;
    $dkim_count = count($dkim);
    if ($dkim_count == 0) {
        $report[] = '<p class="notice notice-error">'.sprintf(__('There is no DKIM record for you domain %s, email providers won\'t be able to authenticate your emails', 'mail-control'), $dkim_host).'</p>';
    } elseif ($dkim_count > 1) {
        $report[] = '<p class="notice notice-error">';
        $report[] = __('There seems to be more tham one dkim record for ', 'mail-control').  $dkim_host ."<br/>";
        $report[] = __('This can cause your emails to get blocked, you should remove the incorrect one :', 'mail-control')."<br/>";
        foreach ($dkim as $record) {
            $report[] = '- '.$record['txt']."<br/>";
        }
        $report[] = '</p>';
    } else {
        $dkim_field =  $dkim[0]['txt'];
        $report[] = '<p class="notice">'.sprintf(__('Your DKIM record : %s', 'mail-control'), $dkim_field);
        $report[] = '<br/><strong>'.__('Please be aware that we only tested the presence of the record, not that its content is correct', 'mail-control').'</strong>';
        $report[] = '</p>';
        $dkim_ok = true;
    }
    return [$dkim_ok, $report];
}

/**
 * Tests the presence of the dkim record
 *
 * @param      string  $domain  The Domain to check
 * @param      array   $report    The reports
 *
 * @return     array  ( test result and report )
 */
function test_dmarc_record(string $domain, array $report)
{
    // Still experimental..
    $report[] = '<h3>'.__('DMARC record verification', 'mail-control').'</h3>';
    $report[] = '<p>'. __('Domain-based Message Authentication, Reporting and Conformance (DMARC) is an email authentication protocol. It gives email domain owners the ability to protect their domain from unauthorized use', 'mail-control').'</p>';
    $report[] = '<p>'. __('Dmarc\'s role is to tell the receiving servers what to do when the sent emails doesn\'t pass SPF and DKIM tests and where to send reports (so you could eventually fix your SPF records or ensure DKIM is well implemented)', 'mail-control').'</p>';

    $dmarc_host = '_dmarc.'.$domain;
    $txt = dns_get_record($dmarc_host, DNS_TXT);
    $dmarc = array_values(array_filter($txt, function ($record) {
        return substr($record['txt'], 0, 7) == 'v=DMARC';
    }));


    $dmarc_ok = false;
    $dmarc_count = count($dmarc);
    if ($dmarc_count == 0) {
        $report[] = '<p class="notice notice-error">'.sprintf(__('There is no DMARK record for you domain %s', 'mail-control'), $dmarc_host).'</p>';
    } elseif ($dmarc_count > 1) {
        $report[] = '<p class="notice notice-error">';
        $report[] = __('There seems to be more tham one DMARK record for', 'mail-control'). ' '. $dmarc_host ."<br/>";
        $report[] = __('This can cause your emails to get blocked, you should remove the incorrect one :', 'mail-control')."<br/>";
        foreach ($dmarc as $record) {
            $report[] = '- '.$record['txt']."<br/>";
        }
        $report[] = '</p>';
    } else {
        $dmarc_field =  $dmarc[0]['txt'];
        $report[] = '<p class="notice">'.sprintf(__('Your DMARK record : %s', 'mail-control'), $dmarc_field);
        $report[] = '<br/><strong>'.__('Please be aware that we only tested the presence of the record, not that its content is correct', 'mail-control').'</strong>';
        $report[] = '</p>';
        $dmarc_ok = true;
    }
    return [$dmarc_ok, $report];
}
