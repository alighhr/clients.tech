<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>
<div class="panel-body" ng-controller="PageController"><!-- Body -->
    <!-- Top container -->
    <div class="clearfix mt-2 mb-2">
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model">Filter <img src="asset/img/filter.svg"></button>&nbsp;
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>&nbsp;
        </div>
        <b> Filters </b><br>
        <span> Series : <span id="Series_GUID"> </span><br>
        <span> Match : <span id="Match_GUID"> </span><br>
        <div class="row w-100 pt-4 m-0">
            <div class="col-lg-12  mb-4" ng-if="MatchWiseData.MatchDetails.MatchName">
                <div class="panel_grp block" >
                    <div class="panel_title" style="background-color: #fcb9a9;">Series: <strong>{{MatchWiseData.MatchDetails.SeriesName}} </strong>(<strong>{{MatchWiseData.MatchDetails.MatchName}}  ({{MatchWiseData.MatchDetails.MatchStartDateTime}})</strong>)</div>
<!--                    <div class="panel_title">Match: </div>-->
                    <div class="panel_title" style="background-color: #fcb9a9;">Match Profit: Rs.<span style="color:green">
                    {{MatchWiseData.Profit}} </span>Match Loss: Rs.<span style="color:red">{{MatchWiseData.loss}} </span></div>
<!--                    <div class="panel_title"></div>-->
                </div>
            </div>
            <div class="col-lg-4  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title">Public Contest Reports </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style">
                            <li>Total Public Contests:</li>
                            <li>Public Completed Contests:</li>
                            <li>Public Canceled Contests:</li>
                        </ul>
                        <ul class="list_style">
                            <li>{{MatchWiseData.ContestDetails.TotalPublicContest}}</li>
                            <li>{{MatchWiseData.ContestDetails.TotalPublicCompleteContest}}</li>
                            <li>{{MatchWiseData.ContestDetails.TotalPublicCancelledContest}}</li>

                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title">Private Contest Reports </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style">
                            <li>Total Private Contests:</li>
                            <li>Private Completed Contests:</li>
                            <li>Private Canceled Contests:</li>
                        </ul>
                        <ul class="list_style">
                            <li>{{MatchWiseData.ContestDetails.TotalPrivateContest}}</li>
                            <li>{{MatchWiseData.ContestDetails.TotalPrivateCompleteContest}}</li>
                            <li>{{MatchWiseData.ContestDetails.TotalPrivateCancelledContest}}</li>

                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title">Joined Users </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style">
                            <li>Category 1:</li>
                            <li>Category 2:</li>
                        </ul>
                        <ul class="list_style">
                            <li>{{MatchWiseData.ContestDetails.TotalJoinedUsersReal}}</li>
                            <li>{{MatchWiseData.ContestDetails.TotalJoinedUsersVirtual}}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title"> Collection Reports (Category 1) </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style">
                            <li>Total Collection</li>
                            <li>Total Wallet Collection</li>
                            <li>Total Bonus Collection</li>
                        </ul>
                        <ul class="list_style">
                            <li>Rs.{{MatchWiseData.TotalJoinContestCollection}}</li>
                            <li>Rs.{{MatchWiseData.TotalDepositCollection}}</li>
                            <li>Rs.{{MatchWiseData.TotalCashBonusCollection}}</li>

                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title"> Total Winning Distribution Reports </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <ul class="list_style">
                            <li>(Category 1)</li>
                            <li>(Category 2)</li>
                        </ul>
                        <ul class="list_style">
                            <li>Rs.{{MatchWiseData.TotalRealUserWinningCollection}}</li>
                            <li>Rs.{{MatchWiseData.TotalVirtualUserWinningCollection}}</li>

                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-12  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title"> Top 5 Winner (Category 1) </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <!--                        <ul class="list_style">
                                                    <li ng-repeat="Lists in MatchWiseData.TopWinners">{{$index + 1}}. {{Lists.FirstName}} (<strong>{{ Lists.Email + ' - ' + Lists.PhoneNumber}}</strong>)</li>
                                                </ul>-->
                        <table class="table table-striped table-condensed table-hover table-sortable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Team Name</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Winning</th>
                                </tr>
                            </thead>
                            <tbody id="tabledivbody">
                                <tr ng-repeat="Lists in MatchWiseData.TopWinners">
                                    <td>#{{$index + 1}}</td>
                                    <td>{{Lists.Username}}</td>
                                    <td>{{Lists.FirstName}}</td>
                                    <td>{{Lists.Email}}</td>
                                    <td>{{Lists.PhoneNumber}}</td>
                                    <td>Rs.{{Lists.TotalWinning}}</td>
                            </tbody>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-12  mb-4">
                <div class="panel_grp block">
                    <div class="panel_title"> Top 5 Loosers (Category 1) </div>
                    <div class="d-flex justify-content-between py-3 px-4">
                        <!--                        <ul class="list_style">
                                                    <li ng-repeat="Lists in MatchWiseData.TopLoosers">{{$index + 1}}. {{Lists.FirstName}} (<strong>{{ Lists.Email + ' - ' + Lists.PhoneNumber}}</strong>)</li>
                                                </ul>-->
                        <table class="table table-striped table-condensed table-hover table-sortable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Team Name</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Losing</th>
                                </tr>
                            </thead>
                            <tbody id="tabledivbody">
                                <tr ng-repeat="Lists in MatchWiseData.TopLoosers">
                                    <td>#{{$index + 1}}</td>
                                    <td>{{Lists.Username}}</td>
                                    <td>{{Lists.FirstName}}</td>
                                    <td>{{Lists.Email}}</td>
                                    <td>{{Lists.PhoneNumber}}</td>
                                    <td>Rs.{{Lists.TotalLosing}}</td>
                            </tbody>
                            </tr>
                        </table>
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
    <div class="modal fade" id="filter_model"  ng-init="getFilterData()">
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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="filter-col" for="CategoryTypeName">Series</label>
                                        <select id="SeriesGUID" name="SeriesGUID" ng-model="SeriesGUID" ng-change="getMatches(SeriesGUID, '')" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="row in filterData.SeiresData" value="{{row.SeriesGUID}}">{{row.SeriesName}}</option>
                                        </select>   
                                    </div>
                                </div>
                            </div>

                            <div class="row">							
                                <div class="col-md-8">
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
                            <!--
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <label class="filter-col">From Date</label>
                                                                    <input type="date" name="FromDate" class="form-control"> 
                                                                    <label class="filter-col">To Date</label>
                                                                    <input type="date" name="ToDate" class="form-control"> 
                                                                </div>
                                                            </div>
                                                        </div>-->

                            <!-- <div class="row">
                                    <div class="col-md-8">
                                            <div class="form-group">
                                                    <label class="filter-col" for="Status">Game type</label>
                                                    <select id="GameType" name="GameType" class="form-control chosen-select">
                                                            <option value="">Please Select</option>
                                                            <option value="Advance">Advance</option>
                                                            <option value="Safe">Safe</option>
                                                    </select>   
                                            </div>
                                    </div>
                            </div>
                            -->
                            <!--                            <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <label class="filter-col" for="Status">Entry type</label>
                                                                    <select id="EntryType" name="EntryType" class="form-control chosen-select">
                                                                        <option value="">Please Select</option>
                                                                        <option value="Multiple">Multiple</option>
                                                                        <option value="Single">Single</option>
                                                                    </select>   
                                                                </div>
                                                            </div>
                                                        </div>-->

                        </div> <!-- form-area /-->
                    </div> <!-- modal-body /-->

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm1').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
                        <button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" onclick="check()" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
                    </div>

                </form>
                <!-- Filter form/ -->
            </div>
        </div>
    </div>

</div><!-- Body/ -->
<script>
function check(){
    $('#Contest_Type').text($('#ContestType').val());
    $('#Contest_Name').text($('#ContestName').val());
    var Seriesvalue = $("#SeriesGUID option:selected");
    $('#Series_GUID').text((Seriesvalue.text() == "Please Select") ? '' : Seriesvalue.text());
    var MatchGUIDvalue = $("#MatchGUID option:selected");
    $('#Match_GUID').text((MatchGUIDvalue.text() == "Please Select") ? '' : MatchGUIDvalue.text());
    $('#Start_Date').text($('#StartDate').val());
    $('#End_Date').text($('#EndDate').val());
}
</script>