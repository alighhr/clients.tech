<header class="panel-heading d-flex align-items-center justify-content-between">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
        <!-- <a href="<?php // echo API_URL.'utilities/createPreContest';?>" target="_blank" class="btn btn-danger btn-danger btn-sm ng-scope">PreContest Cron</a> -->
    
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

    <!-- Top container -->
    <div class="clearfix mt-2 mb-2" >
        <span class="float-left records d-none d-sm-block">
            <span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
        </span>
        <div class="float-right">
            <form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
                <input type="text" class="form-control ml-1" name="Keyword" placeholder="Search">
            </form>
        </div>
        <div class="float-right">
                <!-- <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/search.svg"></button> -->
            <a href="<?php echo base_url().'predraft/preAuctionAdd'?>" target="_blank" class="btn btn-success btn-sm ml-1" >Add Contest</a>
        </div>
        <div class="float-right mr-1">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope mr-1" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>
        </div>
        <!-- <div class="float-right mr-1 ml-1">
            <a href="<?php // echo API_URL.'utilities/createPreContest';?>" target="_blank" class="btn btn-default btn-secondary btn-sm ng-scope">PreContest Cron</a>
        </div> -->
    </div>
    <!-- Top container/ -->


    <!-- Data table -->
    <div class="table-responsive block_pad_md pt-3" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 
        <!-- loading -->
        <p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>

        <!-- data table -->
        <table class="table table-striped table-condensed table-hover table-sortable" ng-if="data.dataList.length">
            <!-- table heading -->
            <thead>
                <tr>
                    <th>Contest Name</th>
                    <th>Type</th>
                    <th>Is Paid?</th>
                    <th>Size</th>
                    <th>Privacy</th>
                    <th class="text-center">Fee</th>
                    <th class="text-center">Is Confirm?</th>
                    <th class="text-center">Entry Type</th>
                    <th class="text-center"># Winners</th>
                    <th class="text-center">Winning Amount</th>
<!-- <th style="width: 100px;" class="text-center">Amount Received</th>
<th style="width: 100px;" class="text-center">Winning Distributed</th> -->
                    <th class="text-center">Action</th>


                </tr>
            </thead>
            <!-- table body -->
            <tbody id="tabledivbody">
                <tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">

                    <td>
                        <div class="content float-left user_table"><strong><a href="javascript:void(0)" >{{row.ContestName}}</a></strong>

                        </div>
                    </td>
                    <td>
                        <p>{{!row.ContestType ? '-' : row.ContestType }}</p>
                    </td>
                    <td align="center">
                        <p>{{row.IsPaid}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.ContestSize}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.Privacy}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.EntryFee}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.IsConfirm}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.EntryType}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.NoOfWinners}}</p>
                    </td>
                    <td align="center">
                        <p>{{row.WinningAmount}}</p>
                    </td>


<!-- <td class="text-center">
        <p>{{row.TotalAmountReceived}}</p>
</td>
<td class="text-center">
        <p>{{row.TotalWinningAmount}}</p>
</td> -->

                    <td class="text-center">
                        <div class="dropdown action_toggle">
                            <button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-h"></i></button>
                            <div class="dropdown-menu dropdown-menu-left">
                                <a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.PreContestID)">Edit</a>
                                <a class="dropdown-item" href="" ng-click="deleteContest(key, row.PreContestID)">Delete</a>
                                <a class="dropdown-item" href="" ng-click="deleteAllUpcomingContest(key, row.PreContestID)">Delete Upcoming Pre Contest</a>
                            </div>
                        </div>
                        <!-- <div class="dropdown" ng-if="row.Status!='Pending'">
                                <span>-</span>
                        </div> -->
                    </td>					
                </tr>
            </tbody>
        </table>

        <!-- no record -->
        <p class="no-records text-center" ng-if="data.noRecords">
            <span ng-if="data.dataList.length">No more records found.</span>
            <span ng-if="!data.dataList.length">No records found.</span>
        </p>
    </div>
    <!-- Data table/ -->


    <!-- Filter Modal -->
    <div class="modal fade" id="filter_model"  ng-init="getFilterData()">
        <div class="modal-dialog modal-lg" role="document">
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
                                        <label class="filter-col" for="Status">Entry type</label>
                                        <select id="EntryType" name="EntryType" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Multiple">Multiple</option>
                                            <option value="Single">Single</option>
                                        </select>   
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="Status">Contest Size</label>
                                        <select id="ContestSizeRange" name="ContestSizeRange" class="form-control chosen-select">
                                            <option value="">Please Select </option>
                                            <option value="0-50">0 - 50</option>
                                            <option value="50-200">50 - 200</option>
                                            <option value="200-500">200 - 500</option>
                                            <option value="500"> Greater than 500</option>
                                        </select>   
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="Status">Contest Type</label>
                                        <select id="ContestType" name="ContestType" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Hot">Hot</option>
                                            <option value="Champion">Champion</option>
                                            <option value="Practice">Practice</option>
                                            <option value="More">More</option>
                                            <option value="Mega">Mega</option>
                                            <option value="Smart Pool">Smart Pool</option>
                                            <option value="Infinity Pool">Infinity Pool</option>
                                            <option value="Winner Takes All">Winner Takes All</option>
                                            <option value="Only For Beginners">Only For Beginners</option>
                                            <option value="Head to Head">Head to Head</option>
                                        </select>  
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="Status">Entry Range</label>
                                        <select id="EntryFeeRange" name="EntryFeeRange" class="form-control chosen-select">
                                            <option value="">Please Select </option>
                                            <option value="0-50">0 - 50</option>
                                            <option value="50-200">50 - 200</option>
                                            <option value="200-500">200 - 500</option>
                                            <option value="500"> Greater than 500</option>
                                        </select>   
                                    </div>
                                </div>
                            </div>

                            <!--                            <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <label class="filter-col" for="Status">Contest Size</label>
                                                                    <select id="ContestSize" name="" class="form-control chosen-select">
                                                                        <option value="">Please Select </option>
                                                                        <option value="0-50">0 - 50</option>
                                                                        <option value="50-200">50 - 200</option>
                                                                        <option value="200-500">200 - 500</option>
                                                                        <option value="500"> Greater than 500</option>
                                                                    </select>   
                                                                </div>
                                                            </div>
                                                        </div>-->

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


    <!-- Filter Modal -->
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
                                        <label class="filter-col" for="ParentCategory">Series</label>
                                        <select id="Series" name="SeriesGUID" ng-model="SeriesGUID" class="form-control chosen-select" ng-change="getMatches(SeriesGUID)">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="Series in filterData.SeiresData" value="{{Series.SeriesGUID}}">{{Series.SeriesName}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Match</label>
                                        <select id="MatchGUID" name="MatchGUID" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option class="filter-matches" ng-repeat="match in MatchData" value="{{match.MatchGUID}}">{{match.TeamNameLocal}} Vs {{match.TeamNameVisitor}} ON {{match.MatchStartDateTime}}</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6">
                                        <div class="form-group">
                                                <label class="filter-col" for="Status">Status</label>
                                                <select id="Status" name="Status" class="form-control chosen-select">
                                                        <option value="">Please Select</option>
                                                        <option value="Pending">Pending</option>
                                                        <option value="Running">Running</option>
                                                        <option value="Cancelled">Cancelled</option>
                                                        <option value="Completed">Completed</option>
                                                </select>   
                                        </div>
                                </div> -->

                            </div>

                        </div> <!-- form-area /-->
                    </div> <!-- modal-body /-->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="$('.filter-matches').remove();$('#filterForm1').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
                        <button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
                    </div>

                </form>
                <!-- Filter form/ -->
            </div>
        </div>
    </div>



    <!-- add Modal -->
    <div class="modal fade" id="add_model">
        <div class="modal-dialog modal-md customContestModal  modal-lg" style="max-width: 900px;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Add Pre Contest</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div ng-include="templateURLAdd"></div>
            </div>
        </div>
    </div>



    <!-- edit Modal -->
    <div class="modal fade" id="edit_model">
        <div class="modal-dialog modal-md customContestModal  modal-lg" style="max-width: 900px;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Edit <?php echo $this->ModuleData['ModuleName']; ?></h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div ng-include="templateURLEdit"></div>
            </div>
        </div>
    </div>

    <!-- status Modal -->
    <div class="modal fade" id="status_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5"><?php echo $this->ModuleData['ModuleName']; ?></h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div ng-include="templateURLEdit"></div>
            </div>
        </div>
    </div>

    <!-- contest joined user Modal -->
    <div class="modal fade" id="contestJoinedUsers_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5"><?php echo $this->ModuleData['ModuleName']; ?></h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div ng-include="templateURLEdit"></div>
            </div>
        </div>
    </div>


    <!-- delete Modal -->
    <div class="modal fade" id="delete_model">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Delete <?php echo $this->ModuleData['ModuleName']; ?></h3>     	
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