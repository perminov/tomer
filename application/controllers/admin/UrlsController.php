<?php
class Admin_UrlsController extends Indi_Controller_Admin {
    
    /**
     * Do parse
     */
    public function parseAction() {

        // Create curl session
        $curl = curl_init();

        // Get Usage-model shortcut
        $model = Indi::model('Usage');

        // Get `usage` entry, that either have never been used, or having most older usage datetime
        if (!$proxy = $model->fetchRow(['`usedAt` = "0000-00-00 00:00:00"', '`toggle` = "y"'], 'RAND()'))
            $proxy = $model->fetchRow(null, '`usedAt` ASC');

        // Prepare headers
        foreach (Indi::model('Header')->fetchAll('`toggle` = "y"', '`move` ASC') as $headerR)
            $hdrA []= $headerR->name . ': ' . $headerR->value;
            
        // Prepare curl session options
        $optA = [
            CURLOPT_URL => $this->row->title,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            //CURLOPT_HTTPPROXYTUNNEL => true,
            CURLOPT_PROXY => $proxy->title,
            CURLOPT_TIMEOUT => 20,
            //CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5
        ];

        // Setup curl session options
        curl_setopt_array($curl, $optA);

        // Fetch response
        $response = curl_exec($curl);

        // Info about what proxy was used
        $info = 'Proxy: ' . $proxy->title . "\n" . '========================' . "\n";

        // If response is boolean false - return curl error
        if ($response === false) jflush(false, nl2br($info) . 'curl_exec() === false: ' . curl_error($curl));

        // Setup last usage datetime
        if ($optA[CURLOPT_PROXY]) $proxy->assign(['usedAt' => date('Y-m-d H:i:s')])->save();

        // Flush response
        jflush(true, '<textarea style="width: 500px; height: 400px;">' . $info . $response . '</textarea>');
    }
}