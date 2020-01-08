<?php
/**
 * Created by IntelliJ IDEA.
 * User: vds
 * Date: 28/07/2016
 * Time: 16.33
 */

namespace Ssslim\Libraries;


class Network
{

    function curl($url, $c_to = 5, $to = 10, $headers = array(), $post = array(), $curl_handler = null) {

        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "spider", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => $c_to,      // timeout on connect
            CURLOPT_TIMEOUT        => $to,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch = $curl_handler !== null ? $curl_handler : curl_init();
        curl_setopt_array( $ch, $options );

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($headers) curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        if ($post) {
            curl_setopt( $ch, CURLOPT_POST, TRUE);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $post);
        }

        $result_o = new \stdClass();
        $result_o->content	 		= curl_exec( $ch );
        $result_o->status			= curl_errno( $ch );
        $result_o->status_msg		= curl_error( $ch );
        $result_o->info				= curl_getinfo( $ch );
        $result_o->server_header = 	preg_match('#server:[\s](.+?)[\n\r]#i', $result_o->content, $expres)  ? $expres[1] : 'n/a';
        $tmp_a = $result_o->info;
        $result_o->c_time = $tmp_a['connect_time'];
        $result_o->t_time = $tmp_a['total_time'];

        if ($curl_handler === null) curl_close( $ch ); // DON'T CLOSE IF CURL HANDLER IS PROVIDED BY THE CALLER
        return $result_o;
    }


}