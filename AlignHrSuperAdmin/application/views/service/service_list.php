<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>

<div class="panel-body" ng-controller="PageController"><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2">
		<span class="float-left records d-none d-sm-block">
			<span ng-if="data.dataList.length" class="h5">Total records: {{data.totalRecords}}</span>
		</span>
		<div class="float-right"><!-- ng-if="filterData.CategoryTypes.length>1" -->
			 <button  class="btn btn-default btn-secondary btn-sm ng-scope" data-toggle="modal" data-target="#filter_model"><img src="asset/img/filter.svg"></button>
			<button class="btn btn-success btn-sm ml-1" ng-click="loadFormAdd();">Add <?php echo "Service";?></button>
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
				   <th class="text-center">Service Image</th>
					<th>Name</th>
					<th>Description</th>
					<th>Fixed Price</th>
					<th>Fixed Time Duration</th>
					<th>Action</th>
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				<tr scope="row" ng-repeat="(key, row) in data.dataList">

				  <td class="listed sm text-center">
						<img ng-if="!row.MediaURL" ng-src="./asset/img/default-category.png">
						<img ng-if="row.MediaURL" ng-src="{{row.MediaURL}}">
					</td>

					<td>
						<strong>{{row.Name}}</strong>
					</td>

					<td>
						<strong>{{row.Description}}</strong>
					</td>
					<td>
						<strong>{{row.Price}}</strong>
					</td>
					<td>
						<strong>{{row.TimeDuration}}</strong>
					</td>

					<td class="text-center">
						<div class="dropdown action_toggle">
							<button class="btn btn-secondary  btn-sm action" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-h"></i></button>
							<div class="dropdown-menu dropdown-menu-left">
								<a class="dropdown-item" href="" ng-click="loadFormEdit(key, row.ServiceGUID)">Edit</a>
								<a class="dropdown-item" href="" ng-click="loadFormDelete(key, row.ServiceGUID)">Delete</a>
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
	<div class="modal fade" id="filter_model"  ng-init="getFilterData()">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Filters</h3>     	
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>

				<!-- Filter form -->
				<form id="filterForm" role="form" autocomplete="off" class="ng-pristine ng-valid">
					<div class="modal-body">
						<div class="form-area">

							<div class="row">
								<div class="col-md-8">
									<div class="form-group">
										<label class="filter-col" for="ServiceTypeName">Srvice Type</label>
										<select id="ServiceType" name="ServiceType" class="form-control chosen-select">
											<option value="">Select Type</option>
											<option value="Fixed">Fixed</option>
                                            <option value="Variable">Variable</option>
										</select>   
									</div>
								</div>
							</div>
							
							<div class="row">	
							<div class="col-md-8">
								<div class="form-group">
									<label class="filter-col" for="ParentCategory">Categories</label>
									<select id="Category" name="CategoryGUID"  ng-model="formData.CategoryGUID" class="form-control chosen-select">
										<option value="">Please Select</option>
										<option ng-repeat="Categories in categoryList" ng-selected="'formData.CategoryGUID==Categories.CategoryGUID'" value="{{Categories.CategoryGUID}}">{{Categories.CategoryName}}</option>
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



	<!-- add Modal -->
	<div class="modal fade" id="add_model">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title h5">Add <?php echo "Service";?></h3>     	
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
					<h3 class="modal-title h5">Edit Service</h3>     	
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



