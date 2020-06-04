<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>

<div class="panel-body" ng-controller="PageController" ng-init="loadRoundList()"><!-- Body -->
    <div class="clearfix mt-2 mb-2"> 
        <span class="float-left records d-none d-sm-block">
            <span ng-if="RoundformData.TotalRecords" class="h5">Total records: {{RoundformData.TotalRecords}}</span>
        </span>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="filter-col" for="ParentCategory"><b>Series Name:</b> {{SeriesData.SeriesName}}</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="filter-col" for="ParentCategory"><b>Series Start Date:</b> {{SeriesData.SeriesStartDate}}</label>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="filter-col" for="ParentCategory"><b>Series End Date:</b> {{SeriesData.SeriesEndDate}}</label>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="filter-col" for="ParentCategory"><b>Series Type:</b> {{RoundformData.Records[0].SeriesType}}</label>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="filter-col"><b>Status:</b> {{SeriesData.Status}}</label>
            </div>
        </div>

    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sno.</th>
                        <th>Round Format</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Auction Draft Play</th>
                        <th></th>
                        <th>Status</th>
                        <th align="center">Total Matches</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr ng-repeat="list in RoundformData.Records" >
                        <td>{{$index + 1}}</td>
                        <td align="center">{{list.RoundFormat}} <input type="hidden" name="SeriesType" value="{{list.SeriesType}}"></td>
                        <td align="center"><input type="text" class="form-control datepicker" name="SeriesStartDateUTC" ng-model="list.SeriesStartDateUTC"></td>
                        <td align="center"><input type="text" class="form-control datepicker" name="SeriesEndDateUTC" ng-model="list.SeriesEndDateUTC"></td>
                        <td>
                            <select id="AuctionDraftIsPlayed" name="AuctionDraftIsPlayed" ng-model="list.AuctionDraftIsPlayed" class="form-control chosen-select">
                                <option value="">Please Select</option>
                                <option value="Yes" ng-selected='list.AuctionDraftIsPlayed == "Yes"' >Yes</option>
                                <option value="No" ng-selected='list.AuctionDraftIsPlayed == "No"' >No</option>
                            </select>
                        </td>
                        <td align="center">
                        	<button type="submit" class="btn btn-success btn-sm" ng-disabled="saveDataLoadings" ng-click="changeStatus(list.RoundID,list.SeriesStartDateUTC, list.SeriesEndDateUTC,list.AuctionDraftIsPlayed,list.SeriesType,list.SeriesID)">Save</button>
                        </td>
                        <td align="center">{{list.Status}}</td>
                        <td align="center">{{list.TotalMatches}}</td>
                        <td align="center">
                        	<div class="dropdown">
								<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8230;</button>
								<div class="dropdown-menu dropdown-menu-left">
									<a class="dropdown-item" target="_blank" href="players?RoundID={{list.RoundID}}">Players
									</a>
								</div>
							</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- hidden parameters -->
    <input type="hidden" name="ContestGUID" value="{{formData.ContestGUID}}">
    <!-- hidden parameters /-->



	<!-- Filter Modal -->
	<div class="modal fade" id="filter_model" >
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Filters</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>

				<!-- Filter form -->
				<form id="filterForm1" role="form" name="form" autocomplete="off" class="ng-pristine ng-valid">
					<div class="modal-body">
						<div class="form-area">

							<div class="row">
								<div class="col-md-8">
									<div class="form-group">
										<label class="filter-col" for="Status">Status</label>
										<select id="StatusID" name="StatusID" class="form-control chosen-select">
											<option value="">Please Select</option>
											<option value="2">Active</option>
											<option value="6">Inactive</option>
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
</div><!-- Body/ -->