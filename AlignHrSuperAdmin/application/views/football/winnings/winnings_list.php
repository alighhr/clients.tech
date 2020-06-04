<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
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
		<div class="float-right mr-1">
			<button class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>
		</div>
		<div class="float-right mr-1">
			<button class="btn btn-default btn-secondary btn-sm ng-scope" ng-click="reloadPage()"><img src="asset/img/reset.svg"></button>
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
					<th>Contest Name</th>
					<th>Contest Type</th>
					<th class="text-center">Contest Joined</th>
					<th class="text-center">Contest Size</th>
					<th class="text-center" class="text-center">Fee</th>
					<th class="text-center" class="text-center">No. of Winners</th>
					<th class="text-center" class="text-center">Winning Amount</th>
					<th class="text-center">Match Date</th>
					<th class="text-center">Status</th>
					<!-- <th style="width: 100px;" class="text-center">Action</th> -->
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">
				
					<td>
						<div class="content float-left user_table"><strong>{{row.ContestName}}</strong>
							<div ng-if="row.TeamNameLocal">({{row.TeamNameLocal}} v/s {{row.TeamNameVisitor}})</div><div ng-if="!row.TeamNameLocal">-</div>
						</div>
					</td>
					<td>
						<p>{{!row.ContestType ? '-' : row.ContestType }}</p>
					</td>
					
					<td class="text-center">
						<p>{{row.TotalJoined}}</p>
					</td>

					<td class="text-center" >
						<p>{{row.ContestSize}}</p>
					</td>
					
					<td class="text-center" >
						<p>{{row.EntryFee}}</p>
					</td>
					
					<td class="text-center" >
						<p>{{row.NoOfWinners}}</p>
					</td>
					<td class="text-center" >
						<p>{{row.WinningAmount}}</p>
					</td>
					<td>
						<p>{{row.MatchStartDateTime}}</p>
					</td>
					<td class="text-center"><span ng-class="{Pending:'text-danger', Running:'text-success',Cancelled:'text-danger',Completed:'text-success'}[row.Status]">{{row.Status}}</span></td> 

					<!-- <td class="text-center" ng-if="row.TotalJoined > 0">
						<div class="dropdown" >
							<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8230;</button>
							<div class="dropdown-menu dropdown-menu-left">
								<a class="dropdown-item" href="" ng-click="loadFormList(key, row.ContestGUID)">Participated Teams</a>
							</div>
						</div>
					</td>
					<td class="text-center" ng-if="row.TotalJoined == 0">
						-
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
	<div class="modal fade" id="filter_model">
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
										<label class="filter-col">From Date</label>
										<input type="date" name="FromDate" class="form-control"> 
										<label class="filter-col">To Date</label>
										<input type="date" name="ToDate" class="form-control"> 
									</div>
								</div>
							</div>

							<div class="row">
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
							</div>
							<div class="row">
								<div class="col-md-8">
									<div class="form-group">
										<label class="filter-col" for="StatusID">Status</label>
										<select id="Status" name="Status" class="form-control chosen-select">
											<option value="">Please Select</option>
											<option value="Pending">Pending</option>
											<option value="Completed">Completed</option>
											<option value="Running">Running</option>
										</select>   
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-8">
									<div class="form-group">
										<label class="filter-col" for="Status">Contest Type</label>
										<select id="ContestType" name="ContestType" class="form-control chosen-select">
											<option value="">Please Select</option>
											<option value="Normal">Normal</option>
											<option value="Hot">Hot</option>
											<option value="Champion">Champion</option>
											<option value="Practice">Practice</option>
											<option value="Winner">Winner</option>
											<option value="Takes All">Takes All</option>
											<option value="Head to Head">Head to Head</option>
											<option value="Only For Beginners">Only For Beginners</option>
										</select>   
									</div>
								</div>
							</div>

						</div> <!-- form-area /-->
					</div> <!-- modal-body /-->

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary btn-sm" onclick="$('#filterForm').trigger('reset'); $('.chosen-select').trigger('chosen:updated');">Reset</button>
						<button type="submit" class="btn btn-success btn-sm" data-dismiss="modal" ng-disabled="editDataLoading" ng-click="applyFilter()">Apply</button>
					</div>

				</form>
				<!-- Filter form/ -->
			</div>
		</div>
	</div>
</div><!-- Body/ -->