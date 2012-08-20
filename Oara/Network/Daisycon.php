<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Daisycon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Daisycon extends Oara_Network{
    /**
     * Export Merchants Parameters
     * @var array
     */
	private $_exportMerchantParameters = null;
    /**
     * Export Transaction Parameters
     * @var array
     */
	private $_exportTransactionParameters = null;
    /**
     * Export Overview Parameters
     * @var array
     */
	private $_exportOverviewParameters = null;
	/**
     * Export Payment Parameters
     * @var array
     */
	private $_exportPaymentParameters = null;
    /**
     * Client 
     * @var unknown_type
     */
	private $_client = null;
	
	private $_credentials = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Daisycon
	 */
	public function __construct($credentials, $options = array())
	{

		parent::__construct($credentials, $options);

		$this->_credentials = $credentials;
		$user = $credentials['user'];
        $password = $credentials['password'];
        $merchantAuth = $credentials['merchantAuth'];
        $transactionAuth = $credentials['transactionAuth'];
        $overviewAuth = $credentials['overviewAuth'];

        
		$loginUrl = 'http://login.daisycon.com/en/index/';
        
		
		$valuesLogin = array(new Oara_Curl_Parameter('login[username]', $user),
							 new Oara_Curl_Parameter('login[password]', $password)
							);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
		$this->_exportMerchantParameters = array(new Oara_Curl_Parameter('export', 'true'),
        										 new Oara_Curl_Parameter('username', $user),
                                                 new Oara_Curl_Parameter('auth', $merchantAuth),
                                                 new Oara_Curl_Parameter('filename', 'programs'),
                                                 new Oara_Curl_Parameter('filetype', 'csv'),
                                                 new Oara_Curl_Parameter('headers', 'true')
                                                 
                                                );
       
        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('export', 'true'),
                                                    new Oara_Curl_Parameter('username', $user),
                                                    new Oara_Curl_Parameter('auth', $transactionAuth),
	                                                new Oara_Curl_Parameter('filename', 'programs'),
	                                                new Oara_Curl_Parameter('filetype', 'csv'),
	                                                new Oara_Curl_Parameter('headers', 'true')
                                                   );
                                                                                           
       $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('export', 'true'),
                                                new Oara_Curl_Parameter('username', $user),
                                                new Oara_Curl_Parameter('auth', $overviewAuth),
                                                new Oara_Curl_Parameter('filename', 'programs'),
                                                new Oara_Curl_Parameter('filetype', 'csv'),
                                                new Oara_Curl_Parameter('headers', 'true')
                                               );
                                               
       $this->_exportPaymentParameters = array();
       

     
                                               
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/welcome/', array());
		$exportReport = $this->_client->get($urls);
		
		$dom = new Zend_Dom_Query($exportReport[0]);
  
      	$results = $dom->query('#loginForm');
 
      	$count = count($results); // get number of matches: 4
		if($count > 0){
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList(){
		$merchants = array();
	
		$valuesFromExport = $this->_exportMerchantParameters;
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/programs/myprograms/?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
        $merchantReportList = Array();
        $num = count($exportData);
        for ($i = 2; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ";");
            $obj = array();
            $obj['cid'] = $merchantExportArray[0];
            $obj['name'] = $merchantExportArray[1];
            $obj['url'] = $merchantExportArray[2];
            $merchants[] = $obj;
        }
        
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		
		$totalTransactions = array();
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('period', $dStartDate->toString("yyyyMMdd")."-".$dEndDate->toString("yyyyMMdd"));
    
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/stats/transactions/?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		
        $exportData = str_getcsv($exportReport[0],"\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],";");
            if (in_array((int)$transactionExportArray[12],$merchantList)){
	            $transaction = Array();
	            $transaction['unique_id'] = $transactionExportArray[0];
	            $merchantId = (int)$transactionExportArray[12];
	            $transaction['merchantId'] = $merchantId;
	            $transactionDate = new Zend_Date($transactionExportArray[3], 'MM-dd-yyyy HH:mm:ss');
	            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	            
	            if ($transactionExportArray[10] != null){
	            	$transaction['custom_id'] = $transactionExportArray[10];
	            }
	            if ($transactionExportArray[7] == 'approved'){
	            	$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
	            } else if ($transactionExportArray[7] == 'pending' || $transactionExportArray[7] == 'potential' || $transactionExportArray[7] == 'open'){
	            	$transaction['status'] = Oara_Utilities::STATUS_PENDING;
	            } else if ($transactionExportArray[7] == 'disapproved' || $transactionExportArray[7] == 'incasso'){
	                $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
	            } else {
	            	throw new Exception("New status $transactionExportArray[7]");
	            }
	            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[18]);
	            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[9]);
	            $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null){
		$overviewArray = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		$urls = array();
		foreach ($merchantList as $merchantId){

			$overviewExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
			$overviewExport[] = new Oara_Curl_Parameter('program', $merchantId);
			$overviewExport[] = new Oara_Curl_Parameter('period', $dStartDate->toString("yyyyMMdd")."-".$dEndDate->toString("yyyyMMdd"));
	        $urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/affiliatemarketing/stats/month/?', $overviewExport);
		}
		$exportReport = $this->_client->get($urls);
		for ($i = 1; $i < count($exportReport); $i++){
            $exportData = str_getcsv($exportReport[$i],"\r\n");
            $num = count($exportData);
            $overviewDate = clone $dStartDate;
            $overviewDate->setHour(0);
            $overviewDate->setMinute(0);
            $overviewDate->setSecond(0);
            for ($j = 1; $j < $num; $j++) {
            	
                $overviewExportArray = str_getcsv($exportData[$j],";");
                
                $obj = array();
                $urlParams = $urls[$i]->getParameters();
                $obj['merchantId'] = $urlParams[6]->getValue();
                
                $overviewDate->setDay($overviewExportArray[0]);
                $obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                            
                $obj['impression_number'] = 0;
                $obj['click_number'] = $overviewExportArray[1];
                $obj['transaction_number'] = 0;
                            
                $obj['transaction_confirmed_commission'] = 0;
                $obj['transaction_confirmed_value'] = 0;
                $obj['transaction_pending_commission'] = 0;
                $obj['transaction_pending_value'] = 0;
                $obj['transaction_declined_commission'] = 0;
                $obj['transaction_declined_value'] = 0;
                $obj['transaction_paid_commission'] = 0;
                $obj['transaction_paid_value'] = 0;
                $transactionDateArray = Oara_Utilities::getDayFromArray($obj['merchantId'], $transactionArray, $overviewDate, true);
            	foreach ($transactionDateArray as $transaction){
                    $obj['transaction_number']++;
                    if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
                        $obj['transaction_confirmed_value'] += $transaction['amount'];
                        $obj['transaction_confirmed_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
                        $obj['transaction_pending_value'] += $transaction['amount'];
                        $obj['transaction_pending_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
                        $obj['transaction_declined_value'] += $transaction['amount'];
                        $obj['transaction_declined_commission'] += $transaction['commission'];
                    } else if ($transaction['status'] == Oara_Utilities::STATUS_PAID){
                        $obj['transaction_paid_value'] += $transaction['amount'];
                        $obj['transaction_paid_commission'] += $transaction['commission'];
                    }
                }
                if (Oara_Utilities::checkRegister($obj)){
                	$overviewArray[] = $obj;
               	}
            }
		}
		
		foreach ($transactionArray as $merchantId => $merchantTransaction){
			foreach ($merchantTransaction as $date => $transactionList){

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission']= 0;
				$overview['transaction_pending_value']= 0;
				$overview['transaction_pending_commission']= 0;
				$overview['transaction_declined_value']= 0;
				$overview['transaction_declined_commission']= 0;
				$overview['transaction_paid_value']= 0;
				$overview['transaction_paid_commission']= 0;
				foreach ($transactionList as $transaction){
					$overview['transaction_number'] ++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_PAID){
						$overview['transaction_paid_value'] += $transaction['amount'];
						$overview['transaction_paid_commission'] += $transaction['commission'];
					}
				}
				$overviewArray[] = $overview;
			}
		}
		
		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();

    	$user = $this->_credentials['user'];
        $password = $this->_credentials['password'];
        
		$loginUrl = 'http://login.daisycon.com/en/index/';
        
		
		$valuesLogin = array(new Oara_Curl_Parameter('login[username]', $user),
							 new Oara_Curl_Parameter('login[password]', $password)
							);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
		
    	$urls = array();
        $urls[] = new Oara_Curl_Request("http://publisher.daisycon.com/en/financial/payments/?fo=true", array());
		$exportReport = $this->_client->get($urls);
		
    	$dom = new Zend_Dom_Query($exportReport[0]);
      	$results = $dom->query('#filter_payments_selection_start_year_id');
		$count = count($results);
		$yearArray = array();
		if ($count == 1){
			$selectNode = $results->current();
			$yearLines = $selectNode->childNodes;
			for ($i = 0;$i < $yearLines->length;$i++) {
				$yearArray[] = $yearLines->item($i)->attributes->getNamedItem("value")->nodeValue;
			}
			
			foreach ($yearArray as $year){
				$valuesFromExport = array();
				$valuesFromExport[] = new Oara_Curl_Parameter('fo', 'true');
				$valuesFromExport[] = new Oara_Curl_Parameter('filter_payments_posted', 'true');
				$valuesFromExport[] = new Oara_Curl_Parameter('filter_payments[selection][start_year]', $year);
				$urls = array();
	        	$urls[] = new Oara_Curl_Request('http://publisher.daisycon.com/en/financial/payments/?', $valuesFromExport);
				$exportReport = $this->_client->post($urls);
				$dom = new Zend_Dom_Query($exportReport[0]);
      			$payments = $dom->query('.financialPaymentsTable');
				foreach ($payments as $payment){
					
					$paymentReport = self::htmlToCsv(self::DOMinnerHTML($payment));
					$paymentExportArray = str_getcsv($paymentReport[2],";");
					if ($paymentExportArray[0] != ""){
						$obj = array();
						$paymentDate = new Zend_Date($paymentExportArray[0], "MM dd yyyy", "en");
			    		$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
						$obj['pid'] = $paymentDate->toString("yyyyMMdd");
						$obj['method'] = 'BACS';
						if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", $paymentExportArray[4], $matches)) {
							$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
						} else {
							throw new Exception("Problem reading payments");
						}
						
						$paymentHistory[] = $obj;
					}
				}
			}
			
		} else {
			throw new Exception('Problem getting the payments');
		}
    	return $paymentHistory;
    }
    /**
     * 
     * Function that Convert from a table to Csv
     * @param unknown_type $html
     */
    private function htmlToCsv($html){
    	$html = str_replace(array("\t","\r","\n"), "", $html);
    	$csv = "";
    	$dom = new Zend_Dom_Query($html);
      	$results = $dom->query('tr');
      	$count = count($results); // get number of matches: 4
      	foreach ($results as $result){
      		$tdList = $result->childNodes;
      		$tdNumber = $tdList->length;
			for ($i = 0;$i < $tdNumber;$i++) {
				$value = $tdList->item($i)->nodeValue;
				if ($i != $tdNumber -1){
					$csv .= trim($value).";";
				} else {
					$csv .= trim($value);
				}
			}
			$csv .= "\n";
      	}
    	$exportData = str_getcsv($csv,"\n");
    	return $exportData;
    }
    /**
     * 
     * Function that returns the inner HTML code 
     * @param unknown_type $element
     */
	private function DOMinnerHTML($element)
	{
	    $innerHTML = "";
	    $children = $element->childNodes;
	    foreach ($children as $child)
	    {
	        $tmp_dom = new DOMDocument();
	        $tmp_dom->appendChild($tmp_dom->importNode($child, true));
	        $innerHTML.=trim($tmp_dom->saveHTML());
	    }
	    return $innerHTML;
	} 
}