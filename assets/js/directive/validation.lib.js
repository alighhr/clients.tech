app.directive('usernameValidation',['$compile','CSS',function($compile,CSS){
	return {
		restrict: 'E',
		scope: true,
		template: '<div style="color:red" class="'+CSS.errormsg_box+'" ng-if="hasError">{{message}}</div>',
		link: function(scope, element, attr){
			scope.form= '';
			scope.field = '';
			if(attr.hasOwnProperty('formObj')&&attr.formObj!='')
			{
				scope.form = attr.formObj;
				scope.objectBased = true;
			}
			if(attr.hasOwnProperty('fieldTarget')&&attr.fieldTarget!='')
			{
				scope.field = attr.fieldTarget;
			}
			if(!scope.$parent[scope.form].hasOwnProperty(scope.field))
			{
				scope.$parent[scope.form][scope.field] = '';
			}
			scope.$watch(function(){
				return scope.$parent[scope.form][scope.field];
			}, function(newVal,oldVal) {
				if(scope.hasOwnProperty('hasError'))
				{
					if(scope.logic(newVal))
					{
						scope.hasError = false;
					}
					else
					{
						scope.message = 'Username must have 6 Characters';
						scope.hasError = true;
					}
				}
				else { scope.hasError = false;}
				if(scope.hasError)
				{
					scope.$parent[scope.form].error[scope.field]=true;
				}
				else
				{
					if(typeof scope.$parent[scope.form]==Object && typeof scope.$parent[scope.form].error == Object)
					{
						delete scope.$parent[scope.form].error[scope.field];
					}			
				}
			}, true);
		},
		controller: function($scope){
			$scope.logic = function(val){
				if(val.length<6)
				{
					return false;
				}
				else
				{
					return true;
				}
			}
			
		}
	};
}]);
/*Email Library*/
app.directive('emailValidation',['$compile','CSS',function($compile,CSS){
	return {
		restrict: 'E',
		scope: true,
		template: '<div style="color:red" class="'+CSS.errormsg_box+'" ng-if="hasError">{{message}}</div>',
		link: function(scope, element, attr){
			scope.form= '';
			scope.field = '';
			if(attr.hasOwnProperty('formObj')&&attr.formObj!='')
			{
				scope.form = attr.formObj;
				scope.objectBased = true;
			}
			if(attr.hasOwnProperty('fieldTarget')&&attr.fieldTarget!='')
			{
				scope.field = attr.fieldTarget;
			}
			if(!scope.$parent[scope.form].hasOwnProperty(scope.field))
			{
				scope.$parent[scope.form][scope.field] = '';
			}
			scope.$watch(function(){
				return scope.$parent[scope.form][scope.field];
			}, function(newVal,oldVal) {
				if(newVal==undefined || newVal==null)
				{
					return false;
				}
				if(scope.hasOwnProperty('hasError'))
				{
					if(scope.logic(newVal))
					{
						scope.hasError = false;
					}
					else
					{
						scope.message = 'Please Enter a valid email';
						scope.hasError = true;
					}
				}
				else { scope.hasError = false;}
				if(scope.hasError)
				{
					if(typeof scope.$parent[scope.form]==Object && typeof scope.$parent[scope.form].error == Object)
					{
					scope.$parent[scope.form].error[scope.field]=true;
					}
					else
					{
						scope.$parent[scope.form].error={};
						scope.$parent[scope.form].error[scope.field]=true;
					}
				}
				else
				{
					if(typeof scope.$parent[scope.form]==Object && typeof scope.$parent[scope.form].error == Object)
					{
						delete scope.$parent[scope.form].error[scope.field];
					}			
				}	
				
			}, true);
		},
		controller: function($scope){
			//Put Whatever logic you want to add
			$scope.logic = function(val){
				var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				
				if(re.test(val))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			
		}
	};
}]);
app.directive('passwordValidation',['$compile','CSS',function($compile,CSS){
	return {
		restrict: 'E',
		scope: true,
		template: '<div style="color:red" class="'+CSS.errormsg_box+'" ng-if="hasError">{{message}}</div>',
		link: function(scope, element, attr){
			scope.form= '';
			scope.field = '';
			if(attr.hasOwnProperty('formObj')&&attr.formObj!='')
			{
				scope.form = attr.formObj;
				scope.objectBased = true;
			}
			if(attr.hasOwnProperty('fieldTarget')&&attr.fieldTarget!='')
			{
				scope.field = attr.fieldTarget;
			}
			if(!scope.$parent[scope.form].hasOwnProperty(scope.field))
			{
				scope.$parent[scope.form][scope.field] = '';
			}
			scope.$watch(function(){
				return scope.$parent[scope.form][scope.field];
			}, function(newVal,oldVal) {
				if(newVal==undefined || newVal==null)
				{
					return false;
				}
				if(scope.hasOwnProperty('hasError'))
				{
					if(scope.logic(newVal))
					{
						scope.hasError = false;
					}
					else
					{
						scope.message = 'Password must have One capital, One Number and 6 character long';
						scope.hasError = true;
					}
				}
				else { scope.hasError = false;}
				if(scope.hasError)
				{
					scope.$parent[scope.form].error[scope.field]=true;
				}
				else
				{   if(typeof scope.$parent[scope.form]==Object && typeof scope.$parent[scope.form].error == Object)
					{
						delete scope.$parent[scope.form].error[scope.field];
					}					
						
				}
				
			}, true);
		},
		controller: function($scope){
			//Put Whatever logic you want to add
			$scope.logic = function(val){
				
				var re = /^(?=.*\d)(?=.*[A-Z])(?!.*[^a-zA-Z0-9])(.{6,15})$/;
				
				if(re.test(val))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			
		}
	};
}]);


var compareTo = function() {
    return {
        require: "ngModel",
        scope: {
            otherModelValue: "=compareTo"
        },
        link: function(scope, element, attributes, ngModel) {
             
            ngModel.$validators.compareTo = function(modelValue) {
                return modelValue == scope.otherModelValue;
            };
 
            scope.$watch("otherModelValue", function() {
                ngModel.$validate();
            });
        }
    };
};
 
app.directive("compareTo", compareTo);