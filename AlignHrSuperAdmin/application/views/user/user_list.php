<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>
<div class="panel-body" ng-controller="PageController" ><!-- Body -->

    <!-- Top container -->
    <div class="clearfix mt-2 mb-2 d_flex">
        <span class="float-left records hidden-sm-down flex_1">
            <span ng-if="data.dataList.length" class="h5">Total Records: {{data.totalRecords}}</span>
        </span>

        <div>
            <div class="float-right">
                <form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
                    <input type="text" class="form-control" name="Keyword" placeholder="Search">
                </form>
            </div>
            <div class="float-right mr-2">		
				<button class="btn btn-success btn-sm ml-1 float-right" ng-click="loadFormAdd();">Add Client</button>
			</div>
            <div class="float-right">
                <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>&nbsp;
            </div>
            <div class="float-right">
                <button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>&nbsp;
            </div>
            <div class="float-right">
                <button class="btn theme_btn btn-secondary btn-sm ng-scope" ng-click="ExportList()">Export</button>&nbsp;
            </div>
        </div>	
    </div>
    <!-- Top container/ -->



    <!-- Data table -->
    <div class="table-responsive block_pad_md" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 

        <!-- loading -->
        <p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>
        <form name="records_form" id="records_form">
            <!-- data table -->
            <table class="table table-striped table-hover" ng-if="data.dataList.length">
                <!-- table heading -->
                <thead>
                    <tr>
                            <!-- <th style="width: 50px;" class="text-center" ng-if="data.dataList.length>1"><input type="checkbox" name="select-all" id="select-all" class="mt-1" ></th> -->	
                        <th>Client</th>
                        <th>Contact No.</th>
                         <th>Business Name</th>
                        <th>Domain</th> 
                        <th>Payment Mode</th>
                        <th class="sort">Registered On <span class="sort_deactive">&nbsp;</span></th>
                        <th class="text-center">Subscription Type</th>
                        <th class="text-center">Start Date</th>
                        <th class="text-center">End Date</th>
                        <th class="text-center">Action</th>

                    </tr>
                </thead>
                <!-- table body -->
                <tbody>
                    <tr scope="row" ng-repeat="(key, row) in data.dataList">

                        <td class="listed sm clearfix table_list">
                            <div class="content float-left user_table"><strong><a>{{row.FullName}}</a></strong>

                                <div ng-if="row.Email || row.EmailForChange" class="user_table"><a>{{row.Email == "" ? row.EmailForChange : row.Email}}</a></div><div ng-if="!row.Email && !row.EmailForChange">-</div>
                            </div>

                        </td> 

                        <td>{{row.PhoneNumber == "" ? row.PhoneNumberForChange : row.PhoneNumber }}</td> 

                        <td>{{row.BusinessName}}</td> 
                        <td>{{row.Domain}}</td> 
                        <td>{{row.PaymentMode}}</td> 
                        <td ng-bind="row.RegisteredOn"></td>  
                        <td>{{row.SubscriptionType}}</td>
                        <td>{{row.StartDate}}</td> 
                        <td>{{row.EndDate}}</td>
                       
                        <td class="text-center">
                            <div class="dropdown action_toggle">
                                <button class="btn btn-secondary  btn-sm action" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" ng-if="data.UserGUID != row.UserGUID"><i class="fa fa-ellipsis-h"></i></button>
                                <div class="dropdown-menu dropdown-menu-left">


                                    <!-- <a class="dropdown-item" target="_blank" href="transactions?UserGUID={{row.UserGUID}}" >Transactions</a> -->
                                    <!-- <a class="dropdown-item" href="javascript:void(0)" ng-click="loadFormChangePassword(key, row.UserGUID)">Change Password</a> -->
                                    <a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.UserGUID)">Edit</a>
                                    <a class="dropdown-item" href="" ng-click="loadFormDelete(key, row.UserGUID)">Delete</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <!-- no record -->
        <p class="no-records text-center" ng-if="data.noRecords">
            <span ng-if="data.dataList.length">No more records found.</span>
            <span ng-if="!data.dataList.length">No records found.</span>
        </p>
    </div>
    <!-- Data table/ -->


    <div class="modal fade" id="filter_model"  ng-init="getFilterData()">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Filters</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <!-- Filter form -->
                <form id="filterForm1" role="form" autocomplete="off" class="ng-pristine ng-valid">
                    <div class="modal-body">
                        <div class="form-area">

                            <div class="row">
                                <div class="col-md-6">
                                  
                                <div class="form-group">
                                    <label class="control-label">Subscription type</label>
                                    <select name="SubscriptionType" id="SubscriptionType" class="form-control chosen-select">
                                        <option value="">Please Select</option>
                                        <option value="Gold">Gold</option>
                                        <option value="Silver">Silver</option>
                                        <option value="Platinium">Platinium</option>
                                    </select>
                                </div>
                                </div>
                            </div>

                        </div> <!-- form-area /-->
                    </div> <!-- modal-body /-->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm1').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
                        <button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
                    </div>

                </form>
                <!-- Filter form/ -->
            </div>
        </div>
    </div>


    <!-- edit Modal -->
    <div class="modal fade" id="edit_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Edit Client</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="edit_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>

    <div class="modal fade" id="changeUserPassword_form" >
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Change Password</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <!-- Filter form -->
                <form id="changePassword_form" role="form" name="changePassword_form" autocomplete="off" class="ng-pristine ng-valid">
                    <div class="modal-body">
                        <div class="form-area">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <input type="password" name="Password" class="form-control" placeholder="New Password">
                                        <input type="hidden" name="UserGUID" class="form-control" value="{{ChangePasswordformData.UserGUID}}">
                                    </div>
                                </div>
                            </div>
                        </div> <!-- form-area /-->
                    </div> <!-- modal-body /-->

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success btn-sm"  ng-disabled="changeCP" ng-click="changeUserPassword()">Submit</button>
                    </div>

                </form>
                <!-- Filter form/ -->
            </div>
        </div>
    </div>

    <!-- add Modal -->
	<div class="modal fade" id="add_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Add Client</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLAdd"></div>
			</div>
		</div>
	</div>
    <!-- Verification Modal -->
    <div class="modal fade" id="Verification_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Verirification</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="Verification_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>
    <!-- Add cash bonus Modal -->
    <div class="modal fade" id="AddCashBonus_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Add Cash Bonus</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="addCash_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>
    <!-- Add cash bonus Modal -->
    <div class="modal fade" id="AddCashBonusDeposit_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Add Cash </h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="addCashDeposit_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>
    
     <div class="modal fade" id="verifyOtp_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Verify OTP </h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="verify_form" name="edit_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>

    <!-- Add referral users list Modal -->
    <div class="modal fade" id="referralUserList_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Referral Users List</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="referralUserList_form" name="referralUserList_form" autocomplete="off" ng-include="templateURLEdit">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>


    <!-- delete Modal -->
    <div class="modal fade" id="delete_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Delete Client</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <!-- form -->
                <form id="edit_form" name="edit_form" autocomplete="off" ng-include="templateURLDelete">
                </form>
                <!-- /form -->
            </div>
        </div>
    </div>


</div><!-- Body/ -->



