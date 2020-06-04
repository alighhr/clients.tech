<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Booking extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Users_model');
        $this->load->model('Booking_model');
    }

     /*
      Name: 			add
      Description: 	Use to add booking.
      URL: 			/booking/add/
     */

    public function add_post() {
        
        /* Validation section */ 
        $this->form_validation->set_rules('Booking[]', 'Booking', 'trim|required');
        if (!empty($this->Post['Booking']) && is_array($this->Post['Booking'])) {
            foreach ($this->Post['Booking'] as $Key => $Value) {
                $this->form_validation->set_rules('Booking[' . $Key . '][ServiceGUID]', 'ServiceGUID', 'trim|required');
                $this->form_validation->set_rules('Booking[' . $Key . '][Quantity]', 'Quantity', 'trim|required|greater_than[0]|less_than[100]');
            }
        }else{
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "Please select services";
            exit;
        }
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        /* Get ServiceIDs from ServiceGUID*/
        for ($I = 0; $I < count($this->Post['Booking']); $I++) {
            $ServiceID = $this->Entity_model->getEntity('E.EntityID', array('EntityGUID' => $this->Post('Booking')[$I]['ServiceGUID'], 'EntityTypeName' => "Service"));
            if(!$ServiceID){
                $this->Return['ResponseCode'] = 500;
                $this->Return['Message'] = "Invalid ServiceGUID.";
                exit;
            }
            $this->Post['Booking'][$I]['ServiceID'] = $ServiceID['EntityID'];
        }

        /* add booking */
        $BookingGUID = $this->Booking_model->addBooking(array_merge($this->Post, array('UserID' => @$this->SessionUserID)));
		if ($BookingGUID) {
            $this->Return['Message'] =	"New booking added successfully.";
            $this->Return['Data']['BookingGUID'] =	$BookingGUID;
		}else{
			$this->Return['ResponseCode'] = 500;
			$this->Return['Message'] = "An error occurred, please try again later.";
		}
        
    }
 
    /*
      Name:          Payment for booking
      Description:  Use to paymentConfiguration
      URL:          /booking/paymentConfiguration/
     */

    public function paymentConfiguration_post() {
        /* Validation section */
        $this->form_validation->set_rules('BookingGUID', 'BookingGUID', 'trim|required|callback_validateEntityGUID[Booking,BookingID]|callback_checkUserBookingPayment[BookingGUID]');
        $this->form_validation->set_rules('PaymentGateway', 'PaymentGateway', 'trim|required|in_list[PayUmoney]');
        $this->form_validation->set_rules('FirstName', 'FirstName', 'trim');
        $this->form_validation->set_rules('Email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('PhoneNumber', 'PhoneNumber', 'trim|numeric');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $PaymentResponse = $this->Booking_model->paymentBooking(array_merge($this->Post, array('Amount' => $this->TotalAmount,'BookingID' => $this->BookingID)), $this->SessionUserID);
        if (empty($PaymentResponse)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data'] = $PaymentResponse;
            $this->Return['Message'] = "Success.";
        }
    }

    /*
      Name:         Payment success
      Description:  Use to update booking payment status
      URL:          /booking/updateBookingPaymentStatus/
     */

    public function updateBookingPaymentStatus_post() {
        /* Validation section */
        $this->form_validation->set_rules('BookingGUID', 'BookingGUID', 'trim|required|callback_validateEntityGUID[Booking,BookingID]|callback_checkUserBookingPayment[BookingGUID]');
        $this->form_validation->set_rules('PaymentGateway', 'PaymentGateway', 'trim|required|in_list[PayUmoney]');
        $this->form_validation->set_rules('PaymentGatewayResponse', 'PaymentGatewayResponse', 'trim');
        $this->form_validation->validation($this);  /* Run validation */
        /* Validation - ends */

        $PaymentResponse = $this->Booking_model->updateBookingPaymentStatus(array_merge($this->Post, array('Amount' => $this->TotalAmount,'BookingID' => $this->BookingID)), $this->SessionUserID);
        if (empty($PaymentResponse)) {
            $this->Return['ResponseCode'] = 500;
            $this->Return['Message'] = "An error occurred, please try again later.";
        } else {
            $this->Return['Data'] = $PaymentResponse;
            $this->Return['Message'] = "Success.";
        }
    }

    /**
     * Function Name: checkUserBookingPayment
     * Description:   To validate user booking payment status
     */
    public function checkUserBookingPayment($BookingGUID) {
        $Exist = $this->db->query('SELECT PaymentStatus,TotalAmount FROM `tbl_booking` WHERE `UserID` = '.$this->SessionUserID.' AND `BookingID` =' . $this->BookingID)->row();
        if (!empty($Exist)) {
            if ($Exist->PaymentStatus == 'Success') {
                $this->form_validation->set_message('checkUserBookingPayment', 'You have been already Payment for this Booking.');
                return FALSE;
            }
            $this->TotalAmount = $Exist->TotalAmount;
        }
        return TRUE;
    }

}