<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Feature_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();	
	}



	/*
	Description: 	Use to get Cetegories
	*/
	function getAttributes($Field='E.EntityGUID', $Where=array(), $multiRecords=FALSE,  $PageNo=1, $PageSize=10){

		$this->db->select($Field);
		$this->db->select('
			CASE E.StatusID
			when "2" then "Active"
			when "6" then "Inactive"
			END as Status', false);
		$this->db->from('tbl_entity E');	
		$this->db->from('set_attributes A');
		$this->db->where("E.EntityID","A.EntityID", FALSE);

		if(!empty($Where['EntityID'])){
			$this->db->where("E.EntityID",$Where['EntityID']);
		}


		$this->db->where("E.StatusID",2);

		/* Total records count only if want to get multiple records */
		if($multiRecords){ 
			$TempOBJ = clone $this->db;
			$TempQ = $TempOBJ->get();
			$Return['Data']['TotalRecords'] = $TempQ->num_rows();
			$this->db->limit($PageSize, paginationOffset($PageNo, $PageSize)); /*for pagination*/
		}else{
			$this->db->limit(1);
		}

		$this->db->order_by('A.AttributeName','ASC');
		$Query = $this->db->get();	
		//echo $this->db->last_query();
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
	Description: 	Use to add new category
	*/
	function addFeature($UserID, $Features, $StatusID){
		$this->db->trans_start();
		$EntityGUID = get_guid();
		/* Add post to entity table and get EntityID. */
		$FeatureID = $this->Entity_model->addEntity($EntityGUID, array("EntityTypeID"=>15, "UserID"=>$UserID, "StatusID"=>$StatusID));

		/* Add category */
		$InsertData = array_filter(array(
			"FeatureID" 		=>	$FeatureID,
			"FeatureGUID" 		=>	$EntityGUID,	
			"Features" 		    =>	$Features
		));
		
		$this->db->insert('tbl_features', $InsertData);

		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE)
		{
			return FALSE;
		}
		return array('FeatureID' => $FeatureID, 'FeatureGUID' => $EntityGUID);
	}



	/*
	Description: 	Use to update category.
	*/
	function editFeature($FeatureID, $Input=array()){
		$UpdateArray = array_filter(array(
			"Features" 			=>	@$Input['Features']
		));

		if(!empty($UpdateArray)){
			/* Update User details to users table. */
			$this->db->where('FeatureID', $FeatureID);
			$this->db->limit(1);
			$this->db->update('tbl_features', $UpdateArray);
		}

		$this->Entity_model->updateEntityInfo($FeatureID, array('StatusID'=>@$Input['StatusID']));
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
	function getFeatures($Field='', $Where=array(), $multiRecords=FALSE,  $PageNo=1, $PageSize=10){
		/*Additional fields to select*/
		$Params = array();
		if(!empty($Field)){
			$Params = array_map('trim',explode(',',$Field));
			$Field = '';
			$FieldArray = array(
				'FeatureID'				=>	'C.FeatureID',			
				'FeatureGUID'			=>	'C.FeatureGUID',
				'Features'			    =>	'C.Features',
			);
			foreach($Params as $Param){
				$Field .= (!empty($FieldArray[$Param]) ? ','.$FieldArray[$Param] : '');
			}
		}

		$this->db->select('C.FeatureGUID, C.FeatureID FeatureIDForUse');
		$this->db->select($Field);
		$this->db->select('
			CASE E.StatusID
			when "2" then "Active"
			when "6" then "Inactive"
			END as Status', false);

		$this->db->from('tbl_features C');
		$this->db->from('tbl_entity E');

		$this->db->where('C.FeatureID','E.EntityID',FALSE);

		if(!empty($Where['FeatureID'])){
			$this->db->where("C.FeatureID",$Where['FeatureID']);
		}      

		if(!empty($Where['FeatureGUID'])){
			$this->db->where("E.EntityGUID",$Where['FeatureGUID']);
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

		$this->db->order_by('C.FeatureID','DESC');
		$Query = $this->db->get();	
		if($Query->num_rows()>0){
			foreach($Query->result_array() as $Record){

				unset($Record['CategoryIDForUse']);
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




}


