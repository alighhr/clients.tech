<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2" ng-if="data.dataList.length"> 
		<div class="float-right">
			<form id="filterForm" role="form" autocomplete="off" ng-submit="applyFilter()" class="ng-pristine ng-valid">
				<input type="text" class="form-control ml-1" name="Keyword" placeholder="Search">
			</form>
		</div>
		<span class="float-left records d-none d-sm-block">
			<span ng-if="data.dataList.length" class="h5">Total Records: {{data.totalRecords}}</span>
		</span>
		<div class="float-right">
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
					<th>Series Name</th>
					<th> Matches </th>
					<th class="text-center"> Status </th>
					<th class="text-center"> Starts In </th>
					<th class="text-center"> End Date </th>
					<!-- <th class="text-center"> Auction Play </th> -->
					<th class="text-center"> Action </th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">

				<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.MenuOrder}}.{{row.CategoryID}}">
					
					<td class="user_table">
						<a href="football/roundList?SeriesGUID={{row.SeriesGUID}}" target="_blank"><strong>{{row.SeriesName}}</strong></a>
					</td>
					<td class="text-center">
						{{row.TotalMatches}}
					</td>
					
					<td class="text-center"><span ng-class="{Inactive:'text-danger', Active:'text-success'}[row.Status]">{{row.Status}}</span></td> 
					<td class="text-center">{{row.SeriesStartDate | myDateFormat}}<br>(<span am-time-ago="row.SeriesStartDate" ></span>)</td> 
					<td class="text-center">{{row.SeriesEndDate | myDateFormat}}<br>(<span am-time-ago="row.SeriesEndDate" ></span>)</td> 
					<!-- <td class="text-center">{{row.AuctionDraftIsPlayed}}</td>  -->

					<td class="text-center">
						<div class="dropdown action_toggle">
							<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-h"></i></button>
							<div class="dropdown-menu dropdown-menu-left">
								<a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.SeriesGUID)">Edit</a>
								<a class="dropdown-item" target="_blank" href="football/players?SeriesGUID={{row.SeriesGUID}}">Players</a>
							</div>
						</div>
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
								<div class="col-md-6">
									<div class="form-group">
										<label class="filter-col">Start Date</label>
										<input type="date" name="SeriesStartDate" class="form-control"> 
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group"> 
										<label class="filter-col">End Date</label>
										<input type="date" name="SeriesEndDate" class="form-control"> 
									</div>
								</div>
								<div class="col-md-6">
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



	<!-- add Modal -->
	<div class="modal fade" id="add_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Add <?php if(!empty($_GET['ParentCategoryGUID'])){echo "Subcategory";}else{echo "Category";}?></h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div ng-include="templateURLAdd"></div>
			</div>
		</div>
	</div>



	<!-- edit Modal -->
	<div class="modal fade" id="edit_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Edit <?php echo $this->ModuleData['ModuleName'];?></h3>     	
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
					<h3 class="modal-title h5">Delete <?php echo $this->ModuleData['ModuleName'];?></h3>     	
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