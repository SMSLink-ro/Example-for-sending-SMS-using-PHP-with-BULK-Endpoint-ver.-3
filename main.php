<?php

/**
 *
 *   SMS Gateway (BULK) (Version 3) integration with SMSLink.ro 
 *   
 *     using SMS Gateway (BULK) Version 3 Endpoint (New)
 *
 *     Supports HTTP and HTTPS protocols
 *     (New) Supports concatenated SMS (longer than 160 characters)
 *     (New) Supports all Romanian networks
 *     (New) Supports all international networks
 *     (New) Supports international phone numbers formatting
 *     (New) Returns the remote Bulk Package ID
 *
 *   System Requirements:
 *
 *     PHP 5 with
 *     
 *         PHP cURL
 *         
 *         Optional, for transmission compression the following compression libraries are needed:
 *         
 *             for Zlib Gzip compression PHP is required to be compiled --with-zlib[=DIR] [and/or]
 *             for bzip2 compression PHP is required to be compiled --with-bz2[=DIR] [and/or]
 *             for LZF compression PHP is required to be compiled --with-lzf[=DIR]
 *
 *   Usage:
 *
 *     See Usage Examples for the SMSLinkSMSGatewayBulkPackage() class starting on line 435
 *
 *     Get your SMSLink / SMS Gateway Connection ID and Password from
 *         https://www.smslink.ro/get-api-key/
 *
 *   @version    2.0
 *   @see        https://www.smslink.ro/sms-gateway-documentatie-sms-gateway.html
 *
 */

class SMSLinkSMSGatewayBulkPackage
{    
    private $connection_id = null;
    private $password      = null;
        
    private $doHTTPS       = true;        
    private $testMode      = false;
    
    protected $endpointHTTP  = "http://www.smslink.ro/sms/gateway/communicate/bulk-v3.php";
    protected $endpointHTTPS = "https://secure.smslink.ro/sms/gateway/communicate/bulk-v3.php";
            
    private $temporaryDirectory = "/tmp";           
    
    public $remotePackageID  = 0;
    public $remoteMessageIDs = array();
    public $errorMessage     = "";
    public $transactionTime  = 0;
    
    private $packageContents = array();
    private $packageStatus = 0;
    
    private $packageFile = array(
            "contentPlain"      => "",
            "contentCompressed" => ""
        );
    
    private $packageValidation = array(
            "hashMD5" => array(
                "contentPlain"      => "",
                "contentCompressed" => ""
            )
        );
    
    protected $clientVersion = 2.0;
    
    protected $compressionMethods = array(
            0 => array("CompressionID" => 0, "Compression" => "No Compression"),
            1 => array("CompressionID" => 1, "Compression" => "Compression using Zlib Gzip"),
            2 => array("CompressionID" => 2, "Compression" => "Compression using bzip2"),
            3 => array("CompressionID" => 3, "Compression" => "Compression using LZF"),
        );
    
    private $compressionMethod = 1;
    
    /**
     *   Initialize SMSLink - SMS Gateway
     *
     *   Initializing SMS Gateway will require the parameters $connection_id and $password. $connection_id and $password can be generated at
     *   https://www.smslink.ro/sms/gateway/setup.php after authenticated with your account credentials.
     *
     *   @param string    $connection_id     SMSLink - SMS Gateway - Connection ID
     *   @param string    $password          SMSLink - SMS Gateway - Password
     *   @param bool      $testMode          SMSLink - SMS Gateway - Test Mode (true or false)
     *
     *   @return void
     */
    public function __construct($connection_id, $password, $testMode = false)
    {
        if (!is_null($connection_id))
            $this->connection_id = $connection_id;
    
        if (!is_null($password))
            $this->password = $password;
         
        if (($testMode == true) or ($testMode == true))
            $this->testMode = $testMode;
        
        if ((is_null($this->connection_id)) or (is_null($this->password)))
            exit("SMS Gateway initialization failed, credentials not provided. Please see documentation.");
    
    }
    
    public function __destruct()
    {
        $this->connection_id = null;
        $this->password = null;
    
        $this->doHTTPS = true;        
    }

    /**
     *   Sets the compression method to be used during sending
     *
     *   @param int    $compressionMethod    the following values are accepted:
     *   
     *                                          0 for No Compression (default)
     *                                          1 for Zlib Gzip (requires PHP to be compiled --with-zlib[=DIR])
     *                                          2 for bzip2     (requires PHP to be compiled --with-bz2[=DIR])
     *                                          3 for LZF       (requires PHP to be compiled --with-lzf[=DIR])
     *
     *   @return bool     true if method was set or false otherwise
     */
    public function setCompression($compressionMethod)
    {
        if ($this->packageStatus == 0)
        {
            if (array_key_exists($compressionMethod, $this->compressionMethods))
                $this->compressionMethod = $compressionMethod;
            
            return true;               
        }
        
        return false;        
    }
    
    private function applyCompression($contentPlain)
    {
        $contentCompressed = "";

        switch ($this->compressionMethod) 
        {
            case 0:
                $contentCompressed = $contentPlain;
                break;
            case 1:
                $contentCompressed = gzcompress($contentPlain, 9);
                break;
            case 2:
                $contentCompressed = bzcompress($contentPlain, 9);
                break;
            case 3:
                $contentCompressed = lzf_compress($contentPlain);
                break;
        }
        
        return $contentCompressed;        
    }    

    private function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    
    /**
     *   Sets the protocol that will be used by SMS Gateway (HTTPS or HTTP).
     *
     *   @param string    $methodName     POST or GET
     *
     *   @return bool     true if method was set or false otherwise
     */
    public function setProtocol($protocolName = "HTTPS")
    {
        $protocolName = strtoupper($protocolName);
    
        if ($protocolName == "HTTPS") $this->doHTTPS = true;
            elseif ($protocolName == "HTTP") $this->doHTTPS = false;
            else return false;
    
        return true;
    }
    
    /**
     *   Returns the protocol that is used by SMS Gateway (HTTPS or HTTP)
     *
     *   @return string     GET or POST possible values
     */
    public function getProtocol()
    {
        return ($this->doHTTPS) ? "HTTPS" : "HTTP";
    }
    
    private function structureCharactersEncode($messageText)
    {
        $messageText = str_replace("\n", "%0A", $messageText);
        $messageText = str_replace(";",  "%3B", $messageText);
        
        return $messageText;
    }
    
    private function cleanCharacters($messageText)
    {
        $messageText = str_replace("\t", "", $messageText); // Tab
        $messageText = str_replace("\r", "", $messageText); // Carriage Return
        
        return $messageText;
    }
    
    /**
     *   Inserts a SMS to SMS Bulk Package
     *
     *   @param int       $localMessageId           Local Message ID from Your System
     *   
     *   @param string    $receiverNumber           Receiver mobile phone number. Phone numbers should be formatted as a Romanian national mobile phone number (07xyzzzzzz)
     *                                              or as an International mobile phone number (00 + Country Code + Phone Number, example 0044zzzzzzzzz).
     *
     *   @param string    $senderId                 (Optional) Sender alphanumeric string:
     *
     *                                                 numeric    - sending will be done with a shortcode (ex. 18xy, 17xy)
     *                                                 SMSLink.ro - sending will be done with SMSLink.ro (use this for tests only)
     *
     *                                                 Any other preapproved alphanumeric sender assigned to your account:
     *
     *                                                     Your alphanumeric sender list:        
	 * 															http://www.smslink.ro/sms/sender-list.php
	 *
     *                                                     Your alphanumeric sender application: 
	 * 															http://www.smslink.ro/sms/sender-id.php
	 *
     *                                                 Please Note:
     *
     *                                                 SMSLink.ro sender should be used only for testing and is not recommended to be used in production. Instead, you
     *                                                 should use numeric sender or your alphanumeric sender, if you have an alphanumeric sender activated with us.
     *
     *                                                 If you set an alphanumeric sender for a mobile number that is in a network where the alphanumeric sender has not
     *                                                 been activated, the system will override that setting with numeric sender.
     *
     *   @param string    $messageText              Message of the SMS, up to 160 alphanumeric characters, or longer than 160 characters.
     *
     *   @param int       $timestampProgrammed    (Optional) Should be 0 (zero) for immediate sending or other UNIX timestamp in the future for future sending
     *
     *   @return bool     true on success or false on failure     
     */    
    public function insertMessage($localMessageId, $receiverNumber, $senderId, $messageText, $timestampProgrammed = 0)
    {
        if (!is_numeric($localMessageId))
            return false;
                    
        $receiverNumber = preg_replace("/[^0-9]/", "", $receiverNumber); // Remove all non-numeric characters
        
        if (!is_numeric($receiverNumber))
            return false;
            
        $messageText = trim($messageText);                               // Strip whitespace from the beginning and end of the message
        $messageText = str_replace("\r\n", "\n", $messageText);          // Converts Carriage Return + Line Feed to Line Feed        
        $messageText = $this->structureCharactersEncode($messageText);   // Encode structure characters
        $messageText = $this->cleanCharacters($messageText);             // Clean unsuported characters                
        $messageText = substr($messageText, 0, 160);
        
        $this->packageContents[] = array(
                "localMessageId"      => $localMessageId,
                "receiverNumber"      => $receiverNumber,
                "senderId"            => $senderId,
                "messageText"         => $messageText,
                "timestampProgrammed" => $timestampProgrammed
            );
            
        return true;            
    }

    /**
     *   Removes a SMS from SMS Bulk Package
     *
     *   @param int       $localMessageId           Local Message ID from Your System
     *   
     *   @return void     
     */
    public function removeMessage($localMessageId)
    {
        foreach ($this->packageContents as $messageKey => $messageData)
            if ($messageData["localMessageId"] == $localMessageId)
                unset($this->packageContents[$messageKey]);                
    }
    
    /**
     *   Returns the Size of the SMS Bulk Package
     *   
     *   @return int
     */
    public function packageSize()
    {
        return sizeof($this->packageContents);        
    }
    
    /**
     *   Sends the SMS Bulk Package to SMSLink
     *
     *   @return bool     true on success or false on failure
     */
    public function sendPackage()
    {
        $timestampStart = $this->microtimeFloat();
        
        $this->remoteMessageIDs = array();
        $this->errorMessage = "";
        
        if (($this->packageStatus == 0) and ($this->packageSize() > 0))
        {
            $temporaryFile = array();
            foreach ($this->packageContents as $messageKey => $messageData)
                $temporaryFile[] = implode(";", $messageData);

            $this->packageFile["contentPlain"] = implode("\r\n", $temporaryFile);
            $this->packageValidation["hashMD5"]["contentPlain"] = md5($this->packageFile["contentPlain"]);
            
            $this->packageFile["contentCompressed"] = $this->applyCompression($this->packageFile["contentPlain"]);
            $this->packageValidation["hashMD5"]["contentCompressed"] = md5($this->packageFile["contentCompressed"]);
            
            $temporaryFilename = tempnam($this->temporaryDirectory, "sms-package-");
            
            if ($temporaryFilename != false)
            {
                file_put_contents($temporaryFilename, $this->packageFile["contentCompressed"]);
                
                $requestData = array(
                        "connection_id"  => $this->connection_id,
                        "password"       => $this->password,
                        "test"           => ($this->testMode == true) ? 1 : 0,
                        "Compression"    => $this->compressionMethod,
                        "MD5Plain"       => $this->packageValidation["hashMD5"]["contentPlain"],
                        "MD5Compressed"  => $this->packageValidation["hashMD5"]["contentCompressed"],
                        "SizePlain"      => strlen($this->packageFile["contentPlain"]),
                        "SizeCompressed" => strlen($this->packageFile["contentCompressed"]),
                        "Timestamp"      => date("U"),
                        "Buffering"      => 1,
                        "Version"        => $this->clientVersion,                        
                        "Receivers"      => $this->packageSize(),
                        "Package"        => "@".$temporaryFilename
                    );
                    
                $ch = curl_init((($this->doHTTPS == true) ? $this->endpointHTTPS : $this->endpointHTTP)."?timestamp=".date("U"));

                curl_setopt($ch, CURLOPT_POST, 1);

                if ((version_compare(PHP_VERSION, '5.5') >= 0)) 
                {
                    $requestData["Package"] = new CURLFile($temporaryFilename);
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                }

                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                
                if ($this->doHTTPS == true)
                {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }

                $requestResponse = curl_exec($ch);
                
                $connectionErrorCode    = curl_errno($ch);
                $connectionErrorMessage = curl_error($ch);
                $requestStatusCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($connectionErrorCode == 0)
                {
                    if (($requestStatusCode >= 200) and ($requestStatusCode <= 299))
                    {
                        $requestResponse = explode(";", $requestResponse);                 
                
                        if ((is_array($requestResponse)) and (sizeof($requestResponse) >= 3))
                        {
                            if ($requestResponse[0] == "MESSAGE")
                            {            
                                $this->remotePackageID = $requestResponse[3];
                                
                                $messagesAssoc = explode(",", $requestResponse[4]);
                                
                                for($i = 0; $i < sizeof($messagesAssoc); $i++)
                                {
                                    $temporaryMessageData = explode(":", $messagesAssoc[$i]);
                                    
                                    $this->remoteMessageIDs[$temporaryMessageData[0]] = array(
                                            "localMessageId"  => $temporaryMessageData[0],
                                            "remoteMessageId" => $temporaryMessageData[1],
                                            "messageStatus"   => $temporaryMessageData[2]
                                        );
                                }
                                
                                $this->packageStatus = 1;
                                
                                $timestampEnd = $this->microtimeFloat();
                                $this->transactionTime = round($timestampEnd - $timestampStart, 2);
                                
                                return true;                    
                            }
                            else 
                            {
                                $this->errorMessage = implode(";", $requestResponse);                        
                            }
                        }
                        else
                        {
                            $this->errorMessage = "ERROR;0;Unexpected response format";
                        }                        
                    }
                    else
                    {
                        $this->errorMessage = "ERROR;0;Unexpected HTTP code ".$requestStatusCode;
                    }
                }
                else
                {
                    $this->errorMessage = "ERROR;0;".$connectionErrorMessage;
                }
                
                curl_close($ch);
            }            
        }
        
        return false;        
    }    
}

/**
 *
 *
 *     Usage Examples for the SMSLinkSMSGatewayBulkPackage() class
 *
 *
 *
 */

/*
 *
 *
 *     Initialize SMS Gateway Bulk Package
 *
 *       Get your SMSLink / SMS Gateway Connection ID and Password from
 *       https://www.smslink.ro/get-api-key/
 *
 *
 *
 */
$BulkSMSPackage = new SMSLinkSMSGatewayBulkPackage("MyConnectionID", "MyConnectionPassword");

/*
 * 
 *    Insert Messages to SMS Package
 *    
 */
$BulkSMSPackage->insertMessage(1, "07xyzzzzzz",   "numeric", "Test SMS 1");
$BulkSMSPackage->insertMessage(2, "+407xyzzzzzz", "numeric", "Test SMS 2");
$BulkSMSPackage->insertMessage(3, "0407xyzzzzzz", "numeric", "Test SMS 3");

/*
 * 
 *    Send SMS Package to SMSLink
 *    
 */
$BulkSMSPackage->sendPackage();

/*
 * 
 *    Process Result
 *    
 */
echo "Remote Package ID: ".$BulkSMSPackage->remotePackageID."<br />";

$statusCounters = array();

if (sizeof($BulkSMSPackage->remoteMessageIDs) > 0)
{
    foreach ($BulkSMSPackage->remoteMessageIDs as $key => $value)
    {
        switch ($value["messageStatus"])
        {
            /**
             * 
             * 
             *     Message Status:     1 
             *     Status Description: Sender Failed
             *     
             *     
             */            
            case 1:
                $timestamp_send = -1;
                
                /* 
                
                    .. do something .. 
                    for example check the sender because is incorrect
                    
                */
                
                echo "Error for Local Message ID: ".$value["localMessageId"]." (Sender Failed).<br />";
                
                $statusCounters["failedSenderCounter"]++;
                
                break;
            /**
             * 
             * 
             *     Message Status:     2 
             *     Status Description: Number Failed
             *     
             *     
             */                                   
            case 2:
                $timestamp_send = -2;
                
                /* 
                
                    .. do something .. 
                    for example check the number because is incorrect    
                    
                */
                
                echo "Error for Local Message ID: ".$value["localMessageId"]." (Incorrect Number).<br />";
                
                $statusCounters["failedNumberCounter"]++;
                
                break;
            /**
             * 
             * 
             *     Message Status:     3
             *     Status Description: Success
             *     
             *     
             */            
            case 3:
                $timestamp_send = date("U");
                /* 
                
                    .. do something .. 

                    Save in database the Remote Message ID, sent in variabile: $value["RemoteMessageID"].
                    Delivery  reports will  identify  your SMS  using our Message ID. Data type  for the 
                    variabile should be considered to be hundred milions (example: 220000000)                    
                    
                */
                
                echo "Succes for Local Message ID: ".
                     $value["localMessageId"].
                     ", Remote Message ID: ".
                     $value["remoteMessageId"].
                     "<br />";
                
                $statusCounters["successCounter"]++;
                
                break;
            /**
             * 
             * 
             *     Message Status:     4 
             *     Status Description: Internal Error or Number Blacklisted
             *     
             *     
             */                            
            case 4:
                $timestamp_send = -4;
                
                /* 
                
                    .. do something .. 
                    for example try again later

                    Internal Error may occur in the following circumstances:

                    (1) Number is Blacklisted (please check the Blacklist associated to your account), or
                    (2) An error occured at SMSLink (our technical support team is automatically notified)
                    
                */
                
                echo "Error for Local Message ID: ".$value["localMessageId"]." (Internal Error or Number Blacklisted).<br />";
                
                $statusCounters["failedInternalCounter"]++;
                
                break;
            /**
             * 
             * 
             *     Message Status:     5 
             *     Status Description: Insufficient Credit
             *     
             *     
             */            
            case 5:
                $timestamp_send = -5;
                
                /* 
                
                    .. do something .. 
                    for example top-up the account
                    
                */
            
                echo "Error for Local Message ID: ".$value["localMessageId"]." (Insufficient Credit).<br />";
            
                $statusCounters["failedInsufficientCredit"]++;
            
                break;
        }
        
        $statusCounters["totalCounter"]++;
        
    }
        
}
else
{
    echo "Error Transmitting Package to SMSLink: ".$BulkSMSPackage->errorMessage."<br />";
    
}

?>