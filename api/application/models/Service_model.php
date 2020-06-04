<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Service_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();	
	}

	/*
	Description: 	Use to add new category
	*/
	function addService($Input = array(),$SessionUserID, $CategoryID, $StatusID = 2){
		$this->db->trans_start();
		$EntityGUID = get_guid();
		/* Add post to entity table and get EntityID. */
		$ServiceID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID"=>16, "UserID"=>$SessionUserID, "StatusID"=>$StatusID));

		/* Add service */
		$InsertData = array_filter(array(
			"ServiceID" 		=>	$ServiceID,
			"ServiceGUID" 		=>	$EntityGUID,	
			"Name" 		        =>	$Input['Name'],
			"ServiceType" 	    =>	$Input['ServiceType'],
			"CategoryID" 		=>	$CategoryID,
			"Description" 		=>	@$Input['Description'],
			"Price" 		    =>	$Input['Price'],
			"TimeDuration" 		=>	$Input['TimeDuration'],
			"VariablePrice"     =>	@$Input['VariablePrice'],
			"VariableTimeDuration" =>	@$Input['VariableTimeDuration'],

		));
		
		$this->db->insert('tbl_services', $InsertData);

		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
		{
			return FALSE;
		}
		return $ServiceID;
	}

	function addServiceFeature($FeatureID,$ServiceID){
        /* Add service */
		$InsertData = array_filter(array(
			"ServiceID" 		=>	$ServiceID,
			"FeatureID" 		=>	$FeatureID,	
		));
		
		$this->db->insert('tbl_services_features', $InsertData);

		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
		{
			return FALSE;
		}
		return $TRUE;
	}

	public function deleteServiceFeature($ServiceID){
	   
		$this->db->where('ServiceID', $ServiceID);
        $this->db->limit(1);
		$this->db->delete('tbl_services_features');

		return $TRUE;
	}



	/*
	Description: 	Use to update category.
	*/
	function editService($ServiceID,$CategoryID, $Input=array(),$StatusID = 2){
		$UpdateArray = array_filter(array(
			"Name" 		        =>	$Input['Name'],
			"ServiceType" 	    =>	$Input['ServiceType'],
			"CategoryID" 		=>	$CategoryID,
			"Description" 		=>	@$Input['Description'],
			"Price" 		    =>	$Input['Price'],
			"TimeDuration" 		=>	$Input['TimeDuration'],
			"VariablePrice"     =>	@$Input['VariablePrice'],
			"VariableTimeDuration" =>	@$Input['VariableTimeDuration'],
		));

		if(!empty($UpdateArray)){
			/* Update User details to users table. */
			$this->db->where('ServiceID', $ServiceID);
			$this->db->limit(1);
			$this->db->update('tbl_services', $UpdateArray);
		}
		return TRUE;
	}

	/*
	Description: 	Use to get Cetegories
	*/
	function getCategoryTypes($Field='E.EntityGUID', $Where=array(), $multiRecords=FALSE,  $PageNo=1, $PageSize=10){


		$this->db->select('CT.*');
		$this->db->select($Field);
		$this->db->select('
			CASE CT.StatusID
			when "2" then "Active"
			when "6" then "Inactive"
			END as Status', false);
		$this->db->from('set_categories_type CT');

		if(!empty($Where['CategoryTypeGUID'])){
			$this->db->where("CT.CategoryTypeGUID",$Where['CategoryTypeGUID']);
		}


		if(!empty($CategoryTypeData)){
			$this->db->where("CT.CategoryTypeID",$CategoryTypeData['CategoryTypeID']);
		}

		$this->db->where("CT.StatusID",2);

		/* Total records count only if want to get multiple records */
		if($multiRecords){ 
			$TempOBJ = clone $this->db;
			$TempQ = $TempOBJ->get();
			$Return['Data']['TotalRecords'] = $TempQ->num_rows();
			$this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /*for pagination*/
		}else{
			$this->db->limit(1);
		}

		$this->db->order_by('CT.CategoryTypeName','ASC');
		$Query = $this->db->get();	
		if($Query->num_rows()>0){
			foreach($Query->result_array() as $Record){
				if(!$multiRecords){
					return $Record;
				}
				$Records[] = $Record;
			}
			$Return['Data']['Records'] = $Records;
			return $Return;
		}
		return FALSE;		
	}

	/*
	Description: 	Use to get Cetegories
	*/
	function getServices($Field='', $Where=array(), $multiRecords=FALSE,  $PageNo=1, $PageSize=10){
		/*Additional fields to select*/
		$Params = array();
		if(!empty($Field)){
			$Params = array_map('trim',explode(',',$Field));
			$Field = '';
			$FieldArray = array(
				'ServiceID'				=>	'S.ServiceID',			
				'ServiceGUID'			=>	'S.ServiceGUID',
				'CategoryGUID'			=>	'C.CategoryGUID',
				'FeatureGUID'           => '(SELECT F.FeatureGUID
                                                        FROM tbl_services_features SF JOIN tbl_features F ON F.FeatureID=SF.FeatureID
                                                        WHERE SF.ServiceID =  S.ServiceID ) AS FeatureGUID',
				//'FeatureGUID'			=>	'F.FeatureGUID',
				'Name'			        =>	'S.Name',
				'ServiceType'			=>	'S.ServiceType',
				'CategoryID'		    =>	'S.CategoryID',
				'Description'			=>	'S.Description',
				'Price'			        =>	'S.Price',
				'TimeDuration'		    =>	'S.TimeDuration',
				'VariablePrice'			=>	'S.VariablePrice',
				'VariableTimeDuration'  =>	'S.VariableTimeDuration',
			);
			foreach($Params as $Param){
				$Field .= (!empty($FieldArray[$Param]) ? ','.$FieldArray[$Param] : '');
			}
		}

		$this->db->select('S.ServiceGUID, S.ServiceID ServiceIDForUse,Name');
		$this->db->select($Field);
		$this->db->select('
			CASE E.StatusID
			when "2" then "Active"
			when "6" then "Inactive"
			END as Status', false);

		$this->db->from('tbl_services S');
		$this->db->from('tbl_entity E');
		$this->db->from('set_categories C');

		$this->db->where('S.ServiceID','E.EntityID',FALSE);
		$this->db->where('S.CategoryID','C.CategoryID',FALSE);

		if(!empty($Where['ServiceID'])){
			$this->db->where("S.ServiceID",$Where['ServiceID']);
		}
		if(!empty($Where['CategoryID'])){
			$this->db->where("S.CategoryID",$Where['CategoryID']);
		}      

		if(!empty($Where['ServiceGUID'])){
			$this->db->where("E.EntityGUID",$Where['ServiceGUID']);
		} 
		if(!empty($Where['Name'])){
			$this->db->where("S.Name",$Where['Name']);
		} 
		if(!empty($Where['ServiceType'])){
			$this->db->where("S.ServiceType",$Where['ServiceType']);
		}  
		if(!empty($Where['Price'])){
			$this->db->where("S.Price",$Where['Price']);
		}          

		/* Total records count only if want to get multiple records */
		if($multiRecords){ 
			$TempOBJ = clone $this->db;
			$TempQ = $TempOBJ->get();
			$Return['Data']['TotalRecords'] = $TempQ->num_rows();
			$this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /*for pagination*/
		}else{
			$this->db->limit(1);
		}

		$this->db->order_by('S.ServiceID','DESC');
		$Query = $this->db->get();	

		if($Query->num_rows()>0){
			foreach($Query->result_array() as $Record){

				$MediaData = $this->Media_model->getMedia('E.EntityGUID MediaGUID, CONCAT("' . BASE_URL . '",MS.SectionFolderPath,M.MediaName) AS MediaURL',array("SectionID" => 'Service',"EntityID" => $Record['ServiceIDForUse']),TRUE);
				$Record['Media'] = ($MediaData ? $MediaData['Data'] : array());

				$Query1 = $this->db->query('SELECT GROUP_CONCAT(DISTINCT F.Features) as FeatureName
				FROM `tbl_features` `F` INNER JOIN tbl_services_features SF ON SF.FeatureID=F.FeatureID WHERE `SF`.`ServiceID` = '.$Record['ServiceIDForUse'].'')->result_array();
			
				$Record['FeatureName'] = (isset($Query1[0]['FeatureName']) ? $Query1[0]['FeatureName'] : '');

				unset($Record['ServiceIDForUse']);
				if(!$multiRecords){
					return $Record;
				}
				if (!empty($Record['Media']['Records'])) {
					$Record['MediaURL'] = $Record['Media']['Records'][0]['MediaURL'];
				}
				$Records[] = $Record;
			}
			$Return['Data']['Records'] = $Records;
			return $Return;
		}
		return FALSE;
		
	}




}



