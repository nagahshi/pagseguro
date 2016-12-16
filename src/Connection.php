<?php

namespace Nagahshi\Pagseguro;

class Connection
{

    public static function request($url)
    {
        try
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = self::curl_exec_follow($ch);
            curl_close($ch);
            return $data;
        } catch (Exception $ex)
        {
            return ['error' => $ex->getMessageBag()];
        }
    }

    public static function post($url, array $data)
    {
        try
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        } catch (Exception $ex)
        {
            return ['error' => $ex->getMessageBag()];
        }
    }

    public static function curl_exec_follow($ch, &$maxredirect = null)
    {
        $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)" .
                " Gecko/20041107 Firefox/1.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (filter_var(ini_get('open_basedir'), FILTER_VALIDATE_BOOLEAN) === false && filter_var(ini_get('safe_mode'), FILTER_VALIDATE_BOOLEAN) === false)
        {
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        } else
        {

            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            if ($mr > 0)
            {
                $original_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $newurl = $original_url;

                $rch = curl_copy_handle($ch);

                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                do
                {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch))
                    {
                        $code = 0;
                    } else
                    {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302)
                        {
                            preg_match('/Location:(.*?)\n/i', $header, $matches);
                            $newurl = trim(array_pop($matches));
                            if (!preg_match("/^https?:/i", $newurl))
                            {
                                $newurl = $original_url . $newurl;
                            }
                        } else
                        {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);

                curl_close($rch);

                if (!$mr)
                {
                    if ($maxredirect === null)
                        trigger_error('Too many redirects.', E_USER_WARNING);
                    else
                        $maxredirect = 0;

                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }

}
