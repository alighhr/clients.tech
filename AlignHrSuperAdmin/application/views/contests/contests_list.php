<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

    <!-- Top container -->
    <div class="clearfix mt-2 mb-2">
        <span class="float-left records hidden-sm-down">
            <span ng-if="data.dataList.length" class="h5">Total Records: {{data.totalRecords}}</span>
        </span>
        <div class="float-right">
            <form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
                <input type="text" class="form-control ml-1" name="Keyword" placeholder="Search">
            </form>
        </div>
        <div class="float-right">
            <button class="btn btn-success btn-sm ml-1" ng-click="loadFormAdd();"> Add Contest </button>
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope mr-1" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>
        </div>
    </div>
    <!-- Top container/ -->


    <!-- Data table -->
    <div class="table-responsive block_pad_md" infinite-scroll="getList()" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 
        <!-- loading -->
        <p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>

        <!-- data table -->
        <table class="table table-striped table-condensed table-hover table-sortable" ng-if="data.dataList.length">
            <!-- table heading -->
            <thead>
                <tr>
    <!-- <th style="width: 100px;">Game Type</th> -->
                    <th>Contest Name</th>
                    <th>Type</th>
                    <th>Game Type</th>
                    <!-- <th style="width: 70px;">Private/Public</th> -->
                    <th>Is Safe Mode</th>
                    <th>Size</th>
                    <th class="text-center">Fee</th>
                    <th class="text-center">Admin Percentage</th>
                    <th class="text-center">Entry Type</th>
                    <th class="text-center"># Winners</th>
                    <th class="text-center">Winning Amount</th>
                    <th class="text-center">Match Date</th>
                    <th class="text-center">Total Joined</th>
                    <th class="text-center">Amount Received</th>
                    <th class="text-center">Winning Distributed</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
        </thead>
            <!-- table body -->
        <tbody id="tabledivbody">
                <tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">

<!-- <td>
                <p>{{row.GameType}}</p>
        </td> -->
                    <td>
                        <div class="content float-left user_table"><strong><a href="javascript:void(0)" ng-click="loadContestJoinedUser(key, row.ContestGUID)">{{row.ContestName}}</a></strong>
                            <div ng-if="row.TeamNameLocal" class="user_table">({{row.TeamNameLocal}} v/s {{row.TeamNameVisitor}})</div><div ng-if="!row.TeamNameLocal">-</div>
                        </div>
                    </td>
                    <td>
                        <p>{{!row.ContestType ? '-' : row.ContestType }}</p>
                    </td>
                    <td>
                        <p>{{!row.GameType ? '-' : row.GameType }}</p>
                    </td>
                    <td>
                        <p>{{row.IsVirtualUserJoined}}</p>
                    </td>
                    <td>
                        <p>{{row.ContestSize}}</p>
                    </td>
                    <td>
                        <p>{{row.EntryFee}}</p>
                    </td>
                    <td>
                        <p align="center">{{row.AdminPercent}}%</p>
                    </td>
                    <td>
                        <p>{{row.EntryType}}</p>
                    </td>
                    <td>
                        <p class="text-center">{{row.NoOfWinners}}</p>
                    </td>
                    <td>
                        <p class="text-center">{{row.WinningAmount}}</p>
                    </td>
                    <td>
                        <p>{{row.MatchStartDateTime}}</p>
                    </td>
                    <td class="text-center">
                        <p>{{row.TotalJoined}}</p>
                    </td>
                    <td class="text-center">
                        <p>{{row.TotalAmountReceived}}</p>
                    </td>
                    <td class="text-center">
                        <p>{{row.TotalWinningAmount}}</p>
                    </td>
                    <td class="text-center"><span ng-class="{Pending:'text - danger', Running:'text - success',Cancelled:'text - danger',Completed:'text - success'}[row.Status]">{{row.Status}}</span></td> 

                    <td class="text-center">
                        <div class="dropdown action_toggle">
                            <button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-h"></i></button>
                            <div class="dropdown-menu dropdown-menu-left">
                                <a class="dropdown-item" href="" ng-if="row.Status == 'Pending'" ng-click="loadFormEdit(key, row.ContestGUID)">Edit</a>
                                <a class="dropdown-item" href="" ng-click="loadFormStatus(key, row.ContestGUID)">Status</a>
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
                                        <label class="filter-col" for="CategoryTypeName">Series</label>
                                        <select id="SeriesGUID" name="SeriesGUID" ng-model="SeriesGUID" ng-change="getMatches(SeriesGUID, '')" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="row in filterData.SeiresData" value="{{row.SeriesGUID}}">{{row.SeriesName}}</option>
                                        </select>   
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Match</label>
                                        <select id="MatchGUID" name="MatchGUID" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="match in MatchData" value="{{match.MatchGUID}}">{{match.TeamNameLocal}} Vs {{match.TeamNameVisitor}} ON {{match.MatchStartDateTime}}</option>
                                        </select>
                                        <small>Select this option to select match according to selected series.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col">From Date</label>
                                        <input type="date" name="FromDate" class="form-control"> 
                                        <label class="filter-col">To Date</label>
                                        <input type="date" name="ToDate" class="form-control"> 
                                    </div>
                                </div>

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
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="Status">Status</label>
                                        <select id="Status" name="Status" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Running">Running</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>   
                                    </div>
                                </div>
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
                            </div>

                            <div class="row">
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

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="Status">Game Type</label>
                                        <select id="GameType" name="GameType" class="form-control chosen-select">
                                            <option value="">Please Select </option>
                                            <option value="Super Six">Super Six</option>
                                            <option value="Battles">Battles</option>
                                            <option value="2nd Innings">2nd Innings</option>
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



    <!-- add Modal -->
    <div class="modal fade" id="add_model">
        <div class="modal-dialog modal-md customContestModal  modal-lg" style="max-width: 900px;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Add Contest</h3>     	
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
        <div class="modal-dialog modal-lg" role="document">
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