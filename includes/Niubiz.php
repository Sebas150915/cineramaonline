<?php
class Niubiz
{
    private $merchantId = "456879852";
    private $user = "integraciones@niubiz.com.pe";
    private $password = "_7z3@8fF";
    private $urlSecurity = "https://apisandbox.vnforappstest.com/api.security/v1/security";
    private $urlSession = "https://apisandbox.vnforappstest.com/api.ecommerce/v2/ecommerce/token/session";
    private $urlAuthorization = "https://apisandbox.vnforappstest.com/api.authorization/v3/authorization/ecommerce";

    public function getAccessToken()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urlSecurity);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode($this->user . ":" . $this->password)
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             throw new Exception('Error cURL Security: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return $response; // Returns pure text token
    }

    public function createSession($accessToken, $amount, $antifraud = [])
    {
        $data = [
            "channel" => "web",
            "amount" => $amount,
            "antifraud" => [
                "clientIp" => $_SERVER['REMOTE_ADDR'],
                "merchantDefineData" => [
                    "MDD4" => "integraciones@niubiz.com.pe",
                    "MDD21" => "0",
                    "MDD32" => "integraciones@niubiz.com.pe",
                    "MDD75" => "Registrado",
                    "MDD77" => "1"
                ]
            ]
        ];
        
        // Merge optional antifraud data
        if (!empty($antifraud)) {
            $data['antifraud'] = array_merge($data['antifraud'], $antifraud);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urlSession . "/" . $this->merchantId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $accessToken",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             throw new Exception('Error cURL Session: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }
    
    public function authorizeTransaction($accessToken, $transactionToken, $purchaseNumber, $amount)
    {
        $data = [
            "channel" => "web",
            "captureType" => "manual",
            "countable" => true,
            "order" => [
                "tokenId" => $transactionToken,
                "purchaseNumber" => $purchaseNumber,
                "amount" => $amount,
                "currency" => "PEN"
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urlAuthorization . "/" . $this->merchantId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: $accessToken",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             throw new Exception('Error cURL Auth: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }
    
    public function getMerchantId() {
        return $this->merchantId;
    }
}
?>
