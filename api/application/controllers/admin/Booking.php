<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Booking extends API_Controller_Secure {

    function __construct() {
        parent::__construct();
        $this->load->model('Booking_model');
    }

    /*
      Name: 	    Listing
      Description: 	Use to list booking.
      URL: 			/api/admin/Booking/getBookings
    */

    public function getBookings_post() { 
      /* Validation section */
      $this->form_validation->set_rules('BookingGUID', 'BookingGUID', 'trim|callback_validateEntityGUID[Booking,BookingID]');
      $this->form_validation->set_rules('EmployeeGUID', 'EmployeeGUID', 'trim|callback_validateEntityGUID[User,UserID]');
      $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|callback_validateEntityGUID[User,UserID]');
      $this->form_validation->validation($this);  /* Run validation */
      /* Validation - ends */ 
      if (!empty($this->Post['EmployeeGUID'])) {
        $EmployeeID = @$this->UserID;
      }
      $BookingData = $this->Booking_model->getBookingList(@$this->Post['Params'], array_merge($this->Post, array('BookingID' => @$this->BookingID, 'UserID' => @$this->UserID, 'EmployeeID' => @$EmployeeID, 'SessionUserID' => $this->SessionUserID)), TRUE, @$this->Post['PageNo'], @$this->Post['PageSize']);
      if ($BookingData) {
              $this->Return['Message'] =	"Booking List.";
              $this->Return['Data'] =	$BookingData['Data'];
  		}else{
  			$this->Return['ResponseCode'] = 500;
  			$this->Return['Message'] = "An error occurred, please try again later.";
  		}        
    }

    /*
      Name:       Assign Employee
      Description:  Use to Assign Employee for booking.
      URL:      /api/admin/booking/assignEmployee
    */

    public function assignEmployee_post() {
      /* Validation section */
      // $this->form_validation->set_rules('SeriesID', 'SeriesID', 'trim|required');
      $this->form_validation->set_rules('BookingGUID', 'BookingGUID', 'trim|required|callback_validateEntityGUID[Booking,BookingID]|callback_getAssignEmployee[BookingGUID]');
      $this->form_validation->set_rules('UserGUID', 'UserGUID', 'trim|required|callback_validateEntityGUID[User,UserID]|callback_validateEmployee[UserGUID]');
      $this->form_validation->validation($this);  /* Run validation */
      /* Validation - ends */
      $UpData = $this->Booking_model->UpdateWhereData('tbl_booking', array('BookingID' => $this->BookingID), array('EmployeeID' =>$this->UserID));
      if ($UpData) {
        $this->Return['Message'] =  "Assign Employee Successfully.";
        $this->Return['Data'] = $BookingData['Data'];
      }else{
        $this->Return['ResponseCode'] = 500;
        $this->Return['Message'] = "An error occurred, please try again later.";
      }        
    }

    /**
     * Function Name: getAssignEmployee
     * Description:   To validate if any Employee Assign
     */
    public function getAssignEmployee($BookingGUID) {
      $Exist = $this->db->query('SELECT 1 FROM `tbl_booking` WHERE `PaymentStatus` = "Success" AND `EmployeeID` IS NOT NULL AND `BookingID` =' . $this->BookingID)->row();
      if ($Exist > 0) {
        $this->form_validation->set_message('getAssignEmployee', 'You have been already Assign Employee for this Booking.');
        return FALSE;
      }
      return TRUE;
    }

    /**
     * Function Name: validateEmployee
     * Description:   To validate only Employee
     */
    public function validateEmployee($UserGUID) {
      $ExistEmployee = $this->db->query('SELECT 1 FROM `tbl_users` WHERE `UserTypeID` = 7 AND `UserID` =' . $this->UserID)->row();
      if (!$ExistEmployee) {
        $this->form_validation->set_message('validateEmployee', 'Employee does not Available.');
        return FALSE;
      } else {
        return TRUE;
      }
    } 

}