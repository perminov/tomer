<?php
class Admin_UrlsController extends Indi_Controller_Admin {
    
    /**
     * Do parse
     */
    public function parseAction() {

        // Create curl session
        $curl = curl_init();

        // Get proxy
        $proxy = Indi::ini('proxy');

        // Prepare headers
        $hdrA = [];
        foreach (Indi::model('Header')->fetchAll('`toggle` = "y"', '`move` ASC') as $headerR)
            $hdrA []= $headerR->title . ': ' . $headerR->value;

        // Prepare curl session options
        $optA = [
            CURLOPT_URL => $this->row->title,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_PROXY => $proxy->path,
            CURLOPT_PROXYUSERPWD => $proxy->auth,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $hdrA
        ];

        // Setup curl session options
        curl_setopt_array($curl, $optA);

        // Fetch response
        $response = curl_exec($curl);

        // If response is boolean false - return curl error
        if ($response === false) jflush(false, 'curl_exec() === false: ' . curl_error($curl));

        // Parse og:title
        if (preg_match('~<meta name="og:title" content="([^"]+) \| LinkedIn"~', $response, $m))
            $this->row->ogTitle = $m[1];
        
        // Parse og:image
        if (preg_match('~<meta property="og:image" content="([^"]+)"~', $response, $m))
            $this->row->ogImage = $m[1];
            
        // Get json
        $json = array_shift(explode('</script>', array_pop(explode('<script type="application/ld+json">', $response))));
        
        // Save response
        $this->row->assign(['response' => $response, 'scraped' => 'y', 'json' => $json])->save();

        // Flush response
        jflush(true, '<textarea style="width: 500px; height: 400px;">' . $response . '</textarea>');
    }
}