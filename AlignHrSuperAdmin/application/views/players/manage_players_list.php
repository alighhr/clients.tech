<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

    <!-- Top container -->
    <div class="clearfix mt-2 mb-2" ng-if="matchDetail">
        <span class="float-left records d-none d-sm-block">
            <span class="h5" ng-if="!AllMatches" >Match : <b>{{matchDetail.TeamNameShortLocal}}</b> v/s <b>{{matchDetail.TeamNameShortVisitor}}</b></span><br>
            <span class="h5" ng-if="!AllMatches" >Match Date : <b>{{matchDetail.MatchStartDateTime}}</b></span><br>
        </span>

    </div>
    <div class="clearfix mt-2 mb-2" ng-if="data.dataList.length">
        <span class="float-left records d-none d-sm-block">
            <span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
        </span>
        <div class="float-right">
            <form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
                <input type="text" class="form-control ml-1" name="Keyword" placeholder="Search">
            </form>
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>&nbsp;
        </div>
        <div class="float-right">
            <button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>&nbsp;
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
                    <th>Player ID</th>
                    <th style="width: 10%;">Player Live ID</th>
                    <th style="width: 25%;">Player's Name</th>
                    <th style="width: 25%;">Team Name</th>
                    <th>Dublicate</th>
                    <th>Playing11</th>
                    <th>IsActive</th>
                    <th ng-if="!AllMatches" >Player's Role</th>
                    <th  ng-if="!SGUID && RoundIDAva">Salary Credit</th>
                    <!--<th style="width: 100px;" >T20</th>
                    <th style="width: 100px;" >T20i</th>
                    <th style="width: 100px;" >ODI</th>
                    <th style="width: 100px;" >TEST</th>-->
                    <!-- <th style="width: 100px;" class="text-center"></th> -->
                    <!-- <th style="width: 100px;" class="text-center">Action</th> -->
                </tr>
            </thead>
            <!-- table body -->
            <tbody id="tabledivbody">
                <tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">
                    <td>
                        <p>{{row.PlayerID}}</p>
                    </td>
                    <td>
                        <p style="word-wrap: anywhere;">{{row.PlayerIDLive}}</p>
                    </td>
                    <td class="listed sm clearfix">
                        <img class="rounded-circle float-left" ng-src="{{row.PlayerPic}}">
                        <div class="content float-left">
                            <strong>{{row.PlayerName}}</strong>
                        </div>
                    </td>
                    <td>
                        <p>{{row.TeamName}}</p>
                    </td>
                    <td>
                        <p>{{(row.IsActive)?'Yes':'No'}}<input type="checkbox" ng-model="row.IsActive" ng-click="dublicatePlayer(row.PlayerID,'active',row.IsActive)" ></p>
                    </td>
                    <td>
                        <p>{{(row.IsPlaying)?'Yes':'No'}} <input type="checkbox" ng-model="row.IsPlaying" ng-click="ShowConfirm(row.PlayerID,'play',row.IsPlaying)" ></p>
                    </td>
                    <td>
                        <p>{{(row.IsActive)?'Yes':'No'}}<input type="checkbox" ng-model="row.IsActive" ng-click="ShowConfirm(row.PlayerID,'active',row.IsActive)" ></p>
                    </td>
                    <td ng-if="!AllMatches">
						<select name="PlayerRole" id="PlayerRole" ng-model="row.PlayerRole" class="form-control chosen-select" ng-change="checkRole(row.PlayerID,row.PlayerRole)">
							<option value="Batsman" >Batsman</option>
							<option value="Bowler" >Bowler</option>
							<option value="AllRounder" >AllRounder</option>
							<option value="WicketKeeper" >WicketKeeper</option>
						</select>
                    </td>
                    <td ng-if="!SGUID && RoundIDAva"><input type="text" class="form-control" ng-model="row.PlayerSalaryCredit"  >
                        <a class="btn btn-success btn-sm" href="" ng-if="!SGUID && RoundIDAva" ng-click="updatePlayerSalary(row.PlayerGUID, matchDetail.MatchGUID, row)">Update</a>
                    </td>
<!--					<td><input type="text" class="form-control numeric" ng-model="row.PlayerSalary.T20Credits"  ></td>
                                        <td><input type="text" class="form-control numeric" ng-model="row.PlayerSalary.T20iCredits"  ></td>
                                        <td><input type="text" class="form-control numeric" ng-model="row.PlayerSalary.ODICredits"  ></td>
                                        <td><input type="text" class="form-control numeric" ng-model="row.PlayerSalary.TestCredits"  ></td>-->
                    <!-- <td ng-if="!AllMatches" class="text-center">


                    </td> -->
                    <!-- <td ng-if="!AllMatches" class="text-center">
                        <div class="dropdown">
                            <button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8230;</button>
                            <div class="dropdown-menu dropdown-menu-left">
                                <a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.PlayerGUID)">Edit</a>
                            </div>
                        </div>
                    </td> -->

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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="filter-col" for="CategoryTypeName">Team Name</label>
                                        <select id="TeamGUID" name="TeamGUID" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option ng-repeat="row in filterData.TeamData" value="{{row.TeamGUID}}">{{row.TeamName}}</option>
                                        </select>   
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="filter-col">Player Role</label>
                                        <select id="Player Role" name="PlayerRole" class="form-control chosen-select">
                                            <option value="">Please Select</option>
                                            <option value="WicketKeeper">Wicket Keeper</option>
                                            <option value="Batsman">Batsman</option>
                                            <option value="AllRounder">All Rounder</option>
                                            <option value="Bowler">Bowler</option>
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
                    <h3 class="modal-title h5">Edit <?php echo $this->ModuleData['ModuleName']; ?></h3>     	
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div ng-include="templateURLEdit"></div>
            </div>
        </div>
    </div>
</div><!-- Body/ -->