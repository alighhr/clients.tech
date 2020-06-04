<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>
<div class="panel-body" ng-controller="PageController"><!-- Body -->
    <!-- Top container -->
    <div class="clearfix mt-2 mb-2" ng-init="getContestName()">
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model">Filter <img src="asset/img/filter.svg"></button>&nbsp;
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>&nbsp;
        </div>
        <div class="row w-100 pt-4 m-0">
            <!--            <div class="col-lg-12  mb-4">
                            <div class="panel_grp block">
                                <div class="panel_title">Series: <strong>{{MatchWiseData.MatchDetails.SeriesName}} </strong></div>
                                <div class="panel_title">Match: <strong>{{MatchWiseData.MatchDetails.MatchName}}  ({{MatchWiseData.MatchDetails.MatchStartDateTime}})</strong></div>
                                <div class="panel_title">Match Profit: Rs.<strong>{{MatchWiseData.Profit}} </strong></div>
                                <div class="panel_title">Match Loss: Rs.<strong>{{MatchWiseData.loss}} </strong></div>
                            </div>
                        </div>-->
            <div class="col-lg-12  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title" style="background-color: #fcb9a9;">Contest Details: {{ContestAnalysis.ContestDetails.ContestName}} - {{ContestAnalysis.ContestDetails.ContestType}}</div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style col-lg-5">
                            <li ng-if="ContestAnalysis.MatchDetails.SeriesName">Series:</li>
                            <li ng-if="ContestAnalysis.MatchID">Match:</li>
                            <li>Category 1 Joined Users:</li>
                            <li>Category 2 Joined Users:</li>
                            <li>Total Deposit (Category 1):</li>
                            <li>Total Bonus (Category 1):</li>
                            <li>Total Winning (Category 1):</li>
                            <li>Profit:</li>
                            <li>Loss:</li>
                        </ul>
                        <ul class="list_style col-lg-7">
                            <li ng-if="ContestAnalysis.MatchDetails.SeriesName"><strong>{{ContestAnalysis.MatchDetails.SeriesName}}</strong></li>
                            <li ng-if="ContestAnalysis.MatchID"><strong>{{ContestAnalysis.MatchDetails.MatchName}}  ({{ContestAnalysis.MatchDetails.MatchStartDateTime}})</strong></li>
                            <li><strong>{{(ContestAnalysis.TotalRealUserJoined) ? ContestAnalysis.TotalRealUserJoined : 0}}</strong></li>
                            <li><strong>{{(ContestAnalysis.TotalVirtualUserJoined) ? ContestAnalysis.TotalVirtualUserJoined : 0}}</strong></li>
                            <li><strong>Rs.{{ContestAnalysis.TotalDepositCollection}}</strong></li>
                            <li><strong>Rs.{{ContestAnalysis.TotalCashBonusCollection}}</strong></li>
                            <li><strong>Rs.{{ContestAnalysis.TotalRealUserWinningCollection}}</strong></li>
                            <li style="color:green"><strong>Rs.{{ContestAnalysis.Profit}}</strong></li>
                            <li style="color:red"><strong>Rs.{{ContestAnalysis.loss}}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Data table -->
    <div class="table-responsive block_pad_md" infinite-scroll-disabled='data.listLoading' infinite-scroll-distance="0"> 
        <!-- loading -->
        <p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>

        <!-- data table -->
    </div>
    <!-- Data table/ -->

    <!-- Filter Modal -->
    <div class="modal fade" id="filter_model"  ng-init="getFilterData();">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5">Get Reports</h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <!-- Filter form -->
                <form id="filterForm1" role="form" autocomplete="off" class="ng-pristine ng-valid">
                    <div class="modal-body">
                        <div class="form-area">

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
                                        </select>  
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col" for="ParentCategory">Contest Name</label>
<!--                                        <input type="text" name="ContestName" class="form-control"> -->
                                        <select id="ContestName" name="ContestName" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="Value in ContestNameList" value="{{Value.ContestName}}">{{Value.ContestName}}</option>
                                        </select>
                                    </div>
                                </div>
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



                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="filter-col">Start Date</label>
                                        <input type="date" name="FromDate" class="form-control"> 
                                    </div>
                                </div> 

                                <div class="col-md-6">
                                    <div class="form-group"> 
                                        <label class="filter-col">End Date</label>
                                        <input type="date" name="ToDate" class="form-control"> 
                                    </div>
                                </div>  </div> 
                        </div>
                    </div>



                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm1').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
                        <button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="getContestReport()">Apply</button>
                    </div>

                </form>
                <!-- Filter form/ -->
            </div>
        </div>
    </div>

</div><!-- Body/ -->