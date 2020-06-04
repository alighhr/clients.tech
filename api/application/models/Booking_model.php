<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Booking_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();	
    }
    
    /*
      Description:  Add booking to system.
     */
    public function addBooking($Input = array()){
        $this->db->trans_start();

        /* Add booking to entity table and get EntityID. */
        $BookingGUID = get_guid();
        $BookingID = $this->Entity_model->addEntity($BookingGUID, array("EntityTypeID" => 17, "StatusID" => 1,"UserID" => @$Input['UserID']));
        $BookingData = array(
            'BookingID' => $BookingID,
            'BookingGUID' => $BookingGUID,
            'UserID' => $Input['UserID']
        );
        $this->db->insert('tbl_booking', $BookingData);

        /*Add services for booking*/
        $TotalAmount = 0;
        foreach ($Input['Booking'] as $Booking) {
            $ServiceData = $this->db->query('SELECT S.Name, S.ServiceType, S.CategoryID, S.Description, S.Price, S.TimeDuration, S.VariablePrice, S.VariableTimeDuration from `tbl_services` S WHERE S.ServiceID = "' . $Booking['ServiceID'] . '" LIMIT 1')->row_array();
            $Services[] = array(
                    'BookingID' => $BookingID,
                    'ServiceID' => $Booking['ServiceID'],
                    'Quantity' => $Booking['Quantity'],
                    'Price' => $ServiceData['Price'],
                    'ServiceDetails' => json_encode($ServiceData)
                );
                $TotalAmount = $TotalAmount + ($ServiceData['Price']*$Booking['Quantity']);
        }
        
        $this->db->insert_batch('tbl_booking_services', $Services);
        
        /**Update total amount of services in booking table */
        $this->db->where('BookingID', $BookingID);
        $this->db->limit(1);
        $this->db->update('tbl_booking', array('TotalAmount' => $TotalAmount));

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        }
        return $BookingGUID;
    }


    /*
      Description: To get joined contest
     */

    function getBookingList($Field = '', $Where = array(), $multiRecords = FALSE, $PageNo = 1, $PageSize = 15)
    {
        $Params = array();
        if (!empty($Field)) {
            $Params = array_map('trim', explode(',', $Field));
            $Field = '';
            $FieldArray = array(
                'BookingID' => 'B.BookingID',
                'TotalAmount' => 'B.TotalAmount',
                'PaymentStatus'=> 'B.PaymentStatus',
                'Services' => "(SELECT CONCAT( '[', GROUP_CONCAT( JSON_OBJECT('ServiceID', BS.ServiceID,'Price', BS.Price,'Quantity', BS.Quantity,'ServiceDetails', BS.ServiceDetails) ), ']' ) FROM tbl_booking_services BS WHERE B.BookingID = BS.BookingID) Services",
            );
            if ($Params) {
                foreach ($Params as $Param) {
                    $Field .= (!empty($FieldArray[$Param]) ? ',' . $FieldArray[$Param] : '');
                }
            }
        }
        $this->db->select('B.BookingID,B.BookingGUID,B.PaymentStatus,U.FirstName,U.Email');
        if (!empty($Field)) {
            $this->db->select($Field, FALSE);
        }
        $this->db->from('tbl_entity E, tbl_booking B,tbl_users U');
        $this->db->where("B.BookingID", "E.EntityID", FALSE);
        $this->db->where("U.UserID", "B.UserID", FALSE);
        if (!empty($Where['Keyword'])) {
            $this->db->group_start();
            $this->db->like("B.PaymentStatus", $Where['Keyword']);
            $this->db->or_like("B.TotalAmount", $Where['Keyword']);
            $this->db->or_like("U.FirstName", $Where['Keyword']);
            $this->db->or_like("U.Email", $Where['Keyword']);
            $this->db->group_end();
        }
        if (!empty($Where['BookingID'])) {
            $this->db->where("B.BookingID", $Where['BookingID']);
        }
        if (!empty($Where['PaymentStatus'])) {
            $this->db->where("B.PaymentStatus", $Where['PaymentStatus']);
        }
        if (!empty($Where['EmployeeID'])) {
            $this->db->where("B.EmployeeID", $Where['EmployeeID']);
        }elseif (!empty($Where['UserID'])) {
            $this->db->where("B.UserID", $Where['UserID']);
        }
        // if (!empty($Where['UserID'])) {
        //     $this->db->where("B.UserID", $Where['UserID']);
        // }
        if (!empty($Where['OrderBy']) && !empty($Where['Sequence'])) {
            $this->db->order_by($Where['OrderBy'], $Where['Sequence']);
        }
        /* Total records count only if want to get multiple records */
        if ($multiRecords) {
            $TempOBJ = clone $this->db;
            $TempQ = $TempOBJ->get();
            $Return['Data']['TotalRecords'] = $TempQ->num_rows();
            if ($PageNo != 0) {
                $this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /* for pagination */
            }
        } else {
            $this->db->limit(1);
        }
        $Query = $this->db->get();
        // echo $this->db->last_query();die;
        if ($Query->num_rows() > 0) { 
            if ($multiRecords) {
                $Records = array();
                foreach ($Query->result_array() as $key => $Record) {
                    $Records[] = $Record;
                    if (in_array('Services', $Params)) {
                        $Records[$key]['Services'] = (!empty($Record['Services'])) ? json_decode($Record['Services'], true) : array();
                       foreach($Records[$key]['Services'] as $key1 => $Record1){
                        $Records[$key]['Services'][$key1]['ServiceDetails'] = (!empty($Record1['ServiceDetails'])) ? json_decode($Record1['ServiceDetails'], true) : array();
                       }
                    }
                }
                $Return['Data']['Records'] = $Records;
                return $Return;
            } 
            else {
                $Record = $Query->row_array();
                if (in_array('Services', $Params)) {
                    $Record['Services'] = (!empty($Record['Services'])) ? json_decode($Record['Services'], true) : array();
                    foreach($Record['Services'] as $key1 => $Record1){
                        $Record['Services'][$key1]['ServiceDetails'] = (!empty($Record1['ServiceDetails'])) ? json_decode($Record1['ServiceDetails'], true) : array();
                    }
                }
                return $Record;
            }
        }
        return FALSE;
    }

    /*
      Description: To update Data
     */
    public function UpdateWhereData($Table, $Where, $Data) {
        $this->db->where($Where);
        $this->db->limit(1);
        $this->db->update($Table,$Data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;

    }

    /*
      Description: To update Data
     */
    public function updateBookingPaymentStatus($Input,$UserID) {
        
        /* Update Booking Status */ 
        $this->db->where('BookingID' , $Input['BookingID']);
        $this->db->limit(1);
        $this->db->update('tbl_booking',array('PaymentStatus' => 'Success'));

        /* Add Transaction */
        $WalletData = array(
                            'UserID' => $UserID,
                            'Amount' => $Input['Input'],
                            'WalletAmount' => $Input['Input'],
                            'Currency' => 'INR',
                            'PaymentGateway' => $Input['PaymentGateway'],
                            'TransactionType' => 'Dr',
                            'Narration' => 'Services Booking',
                            'EntityID' => $Input['BookingID'],
                            'PaymentGatewayResponse' => $Input['PaymentGatewayResponse'],
                            'StatusID' => 5
                        );
        $this->Users_model->addToWallet($WalletData, $UserID, 5);
        return TRUE;
    }

    /*
      Description: To payment for booking
     */

    public function paymentBooking($Input = array(), $UserID) {
        $TransactionID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $PaymentResponse = array();
        if ($Input['PaymentGateway'] == 'PayUmoney') {
            /* Generate Payment Hash */
            $Amount = (strpos(@$Input['Amount'], '.') !== FALSE) ? @$Input['Amount'] : @$Input['Amount'] . '.0';
            $HashString = PAYUMONEY_MERCHANT_KEY . '|' . $TransactionID . "|" . $Amount . "|" . $Input['BookingID'] . "|" . @$Input['FirstName'] . "|" . @$Input['Email'] . "|||||||||||" . PAYUMONEY_SALT;

            /* Generate Payment Value */
            $PaymentResponse['Action'] = PAYUMONEY_ACTION_KEY;
            $PaymentResponse['MerchantKey'] = PAYUMONEY_MERCHANT_KEY;
            $PaymentResponse['Salt'] = PAYUMONEY_SALT;
            $PaymentResponse['MerchantID'] = PAYUMONEY_MERCHANT_ID;
            $PaymentResponse['Hash'] = strtolower(hash('sha512', $HashString));
            $PaymentResponse['TransactionID'] = $TransactionID;
            $PaymentResponse['Amount'] = $Amount;
            $PaymentResponse['Email'] = @$Input['Email'];
            $PaymentResponse['PhoneNumber'] = @$Input['PhoneNumber'];
            $PaymentResponse['FirstName'] = @$Input['FirstName'];
            $PaymentResponse['ProductInfo'] = @$Input['BookingID'];
            $PaymentResponse['SuccessURL'] = SITE_HOST . ROOT_FOLDER . 'myAccount?status=success';
            $PaymentResponse['FailedURL'] = SITE_HOST . ROOT_FOLDER . 'myAccount?status=failed';
            return $PaymentResponse;
        }
        return FALSE;
    }
}