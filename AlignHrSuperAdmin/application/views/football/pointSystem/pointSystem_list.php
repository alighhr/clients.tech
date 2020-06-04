<header class="panel-heading">
  <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle'];?></h1>
</header>
<div class="panel-body" ng-controller="PageController" ng-init="getList()"><!-- Body -->

	<!-- Top container -->
	<div class="clearfix mt-2 mb-2">
		
			<div class="form-group float-right" style="display: none;">
			  	<select class="form-control" ng-model="PointsCategory" ng-change="getList()">
					<option value="Normal">Normal</option>
				</select>
		  </div>				
		
	</div>
	<!-- Top container/ -->


	<!-- Data table -->
	<div class="table-responsive block_pad_md" > 
		<!-- loading -->
		<p ng-if="data.listLoading" class="text-center data-loader"><img src="asset/img/loader.svg"></p>
		<form method="post" id="generalPoint_form" name="generalPoint_form"  autocomplete='off'>
		<!-- data table for General Points -->
		<table class="table table-striped table-condensed table-hover table-sortable" ng-show="data.dataList.length > 0">
			<!-- table heading -->
			<thead>
				<tr>
					<th >Type of Points (All Points)</th>
					<th style="width: 200px;">Point Value</th>
<!--					<th style="width: 200px;">ODI</th>
					<th style="width: 200px;" >TEST</th>-->
				</tr>
			</thead>
			<!-- table body -->
			<tbody id="tabledivbody">
				
					<tr scope="row" ng-repeat="(key, row) in data.dataList" id="sectionsid_{{row.PointsTypeGUID}}" >
					
						<td>
							<strong>{{row.PointsTypeDescprition}}</strong>
						</td>
						<td>
							<input type="text" class="form-control " name="PointsValue[]" ng-model="row.PointsValue" ng-value="{{row.PointsValue | number : 2 }}" >
                                                        <input type="hidden" name="PointsTypeGUID[]" value="{{row.PointsTypeGUID}}">
                                                </td>
					</tr>
				
			</tbody>
		</table>
		<button class="btn btn-success btn-sm float-right" ng-click="updateGeneralPoints()" >	Submit</button>
		</br>
		</form>
		</br>
		<!-- no record -->
		<p class="no-records text-center" ng-if="data.noRecords">
			<span ng-if="data.dataList.length">No more records found.</span>
			<span ng-if="!data.dataList.length">No records found.</span>
		</p>
	</div>
	<!-- Data table/ -->


</div><!-- Body/ -->