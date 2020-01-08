<?php
/**
 * Created by IntelliJ IDEA.
 * User: kRs
 * Date: 01/07/2016
 * Time: 12.15
 */
namespace Ssslim\Libraries\Cache;

use Ssslim\Core\Libraries\Logger;

//require_once(APPPATH . 'libraries/Cache/Cache.php');

class CacheFactory
{
    private $logger;
    private $baseDir = "cache/";

    /**
     * Cache constructor.
     * @param \Ssslim\Core\Libraries\Logger $logger
     */
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * factory method, returns a new instance of Class already initialized with data
     *
     * @param $id
     * @param bool $packed
     * @return Cache
     *
     */

    public function get($id, $isPacked = true) {
        $rawData = $this->readCacheFile($id, $isPacked);
        
        if (is_array($rawData) && isset($rawData['x']) && isset($rawData['d'])) {
            // VALID CACHE
            return new Cache($id, $rawData['d'], $rawData['x'], true, $isPacked, $this);
        }

        // EXPIRED/NOT EXISTING CACHE
        return new Cache($id, array(), 0, false, $isPacked, $this);
    }

    private function makeFileName($id) {
        return $this->baseDir.$id.'.c';
    }
    
    public function writeCacheFile($id, $userData, $expirationTs = null, $mustPack = false)
    {
        $fileName = $this->makeFileName($id);
        $dirPath = dirname($fileName);

        if (!file_exists($dirPath)) {
            if ( ! @mkdir($dirPath, 0777, true)) return FALSE;
        }

        $data = array ("d" => $userData);
        if ($expirationTs !== null) $data['x'] = $expirationTs;

        $data = serialize($data);
        if ($mustPack) $data = gzcompress($data, 9);

        if ( !($fp = @fopen($fileName, 'wb')) ) return false;

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);
        //dd_log('CW '.$id.' '.strlen($sdata), "logs/file_cache_written.log");

        return true;
    }

    public function readCacheFile($id, $isPacked = false, $ignoreExpiration = false)
    {
        $fileName = $this->makeFileName($id);

        if (!file_exists($fileName)) return false;
        $r = @file_get_contents($fileName);
        if ($r != false) {
            if ($isPacked) {
                $data = @gzuncompress($r);
                if ($data === false) {
                    dd_log('ERROR ** ERROR UNPACKING CACHE FILE '.$id.' - LENGHT: '.strlen($r) , 'logs/file_cache_errors.log');
                    return false;
                }
            }
            else $data = $r;

            $data_raw  = unserialize($data);

            if (!$ignoreExpiration && !empty($data_raw['x']) && time() >= $data_raw['x']) { // cache file expired
                $t_ext = time() + 300;
                $this->writeCacheFile($id, $data_raw['d'], $t_ext, $isPacked);
                //dd_log('CE '.$id. " ".($CI->uri->uri_string()), "logs/file_cache_expired.log");
                return false;
            }

            return $data_raw;
        }
        else {
            $this->logger->log('WARNING ** CACHE FILE '.$id.' EMPTY (size: '.@filesize($id).') on cache read, file_get_contents return type: '.gettype($r)." '$r'", Logger::INFO, 'file_cache_errors.log', Logger::BRIEF);
            // die();
            return false;
        }
    }

    /* type can be 1 for local expiration, 2 for FA mirrors, , 4 for webtvs */
    function invalidateCacheFile($id, $isPacked = true, $type = 3)
    {
        $rawData = $this->readCacheFile($id, $isPacked, true);
        $this->writeCacheFile($id, $rawData, time() - 300, $isPacked);
    }

        /*        if ($type & 6) {	// REMOTE

                    return false;

                    // TODO implement proper remote expiration
                    $ci =& get_instance();
                    $csid = $ci->config->item('server_id');

                    if ($type & 2) $srvs = $ci->config->item("reset_cache_urls"); // FA MIRRORS
                    else $srvs = $ci->config->item("reset_ext_webtvs_cache_urls"); // EXT WEBTVS

                    if ($srvs) { // REMOTE
                        foreach($srvs as $sid => $sc) {
                            if ($sid == $csid) continue;

                            $c_r = fa_curl($sc."service.php?do=clear_cache&id=".urlencode($id));
                            // if ($c_r->content === false || $c_r->content != '1') dd_log($sc.' \''.$id.'\''.($c_r->status != 0 ? ' E: '.$c_r->status.' '.$c_r->status_msg : '').' '.$c_r->content, 'logs/file_cache_errors.log', false, true);
                            if ($c_r->content === false) dd_log($sc.' \''.$id.'\''.($c_r->status != 0 ? ' E: '.$c_r->status.' '.$c_r->status_msg : '').' '.$c_r->content, 'logs/file_cache_errors.log', false, true);
                        }
                    }
                }*/
}