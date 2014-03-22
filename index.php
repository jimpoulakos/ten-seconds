<?php 

/**
 * Bootstrapping files for generating autoloaders and other core files/variables. You don't need to see these, so move along.
 */
define('__swf__', true);
include './bootstrap.php';

/**
 * load_database:
 * This function loads Idiorm and Paris, as well as sets the database credentials.
 */
load_database();

/*
// This is the query to build the database table for this application.
$sql = "create table swf_post ( id int(11) not null auto_increment primary key, message varchar(140), created_on datetime ) engine=InnoDb";
ORM::get_db()->exec($sql);
//*/

class SwfPost extends Model{}

/**
 * This removes anything older than 10 seconds from the database any time this page is loaded.
 */
$date = date('Y-m-d H:i:s', strtotime('-10 seconds'));
$sql = "delete from swf_post where created_on < '{$date}'";
ORM::get_db()->exec($sql);


if($_SERVER['REQUEST_METHOD'] == 'POST'){
	/**
	 * Data is sent via request body from AngularJS in non-GET requests.
	 */
	$data = json_decode(file_get_contents('php://input'));

	if($data->task == 'save'){
		$model = Model::factory('SwfPost')->create();
		$model->created_on = date('Y-m-d H:i:s');
		$model->message = $data->message;
		$model->save();
	} else {
		$models = Model::factory('SwfPost')->find_many();
		$response = array();
		foreach($models as $model){
			$response[] = array('message' => $model->message, 'id' => $model->id);
		}
		echo json_encode($response);
	}
	exit; die;
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<title>Say Something - Jim Poulakos</title>

	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" id="twitter-bootstrap-css-core">
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootswatch/3.1.1/flatly/bootstrap.min.css" id="twitter-bootstrap-flatly-theme">
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" id="font-awesome-css-core">

	<style>
		.animate.ng-enter, .animage.ng-move {
			-webkit-transition: 1s linear all;
			-moz-transition: 1s linear all;
			-ms-transition: 1s linear all;
			transition: 1s linear all;
			opacity: 0;
		}
		.animate.ng-enter.ng-enter-active,
		.animate.ng-move.ng-move-active {
			opacity: 1;
		}

		.animate.ng-leave {
			-webkit-animation: 1s _fadeout;
			-moz-animation: 1s _fadeout;
			-ms-animation: 1s _fadeout;
			animation: 1s _fadeout;
		}

		@keyframes _fadeout {
			from { opacity: 1; }
			to { opacity: 0; }
		}

		@-webkit-keyframes _fadeout{
			from { opacity: 1; }
			to { opacity: 0; }
		}
		@-moz-keyframes _fadeout{
			from { opacity: 1; }
			to { opacity: 0; }
		}
		@-ms-keyframes _fadeout{
			from { opacity: 1; }
			to { opacity: 0; }
		}
	</style>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.15/angular.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.15/angular-resource.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.15/angular-animate.min.js"></script>
</head>
<body ng-app="TenSecondsApp" ng-controller="TenSecondsController">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-4">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h3 class="panel-title">Say Something</h3>
					</div>
					<div class="panel-body">
						<form role="form">
							<div class="form-group" ng-class="{'has-warning has-feedback': messages.message.length>140}">
								<label for="message" class="sr-only control-label">Say Something</label>
								<input type="text" class="form-control" id="message" placeholder="Say Something" ng-model="messages.message">
								<span class="pull-right help-block" ng-show="messages.message.length>140">
									<em><i class="glyphicon glyphicon-warning-sign" ></i> Your text is too long and will be automatically shortened. ({{messages.message.length}} chars)</em>
								</span>
								<span class="pull-right help-block text-success" ng-hide="messages.message.length>140"><em>({{messages.message.length}} chars)</em></span>
							</div>
						</form>
					</div>
					<div class="panel-body">
						<p><small><em>Anything you say here will delete itself after ten seconds. Additionally, anything longer than 140-characters will simply be cut off.</em></small></p>
					</div>

					<div class="panel-footer">
						<p>
						Built with love using <a target="_blank" href="http://angularjs.org/">AngularJS</a>, <a target="_blank" href="http://getbootstrap.com/">Twitter Bootstrap</a>, <a target="_blank" href="http://j4mie.github.io/idiormandparis/">Idiorm &amp; Paris</a>, and <a target="_blank" href="http://www.php.net/">PHP</a>.
						</p>
					</div>
				</div>
			</div>
			<div class="col-md-8">
				<ul class="list-group">
					<li class="animate list-group-item" ng-repeat="message in messages._items">{{message.message}}</li>
				</ul>
			</div>
		</div>
	</div>

	<script id="twitter-bootstrap-javascript-core" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	<script>
		angular.module('TenSecondsApp', ['ngResource', 'ngAnimate'])
		.factory('Api', ['$resource', function($resource){
			return $resource('/', {}, {
				post: { method:'POST', isArray: true }
			});
		}])
		.controller('TenSecondsController', ['$scope', '$interval', 'Api', function($scope, $interval, Api){
			$scope.messages = {
				_items: []
				, is_adding: false
				, message: ''
				, update_queue: function(){
					var self = $scope.messages;

					Api.post({}, {task:'load'}
						, function(d){
							// Not currently any items, just set it to the response and quit.
							if(self._items.length < 1){
								self._items = d;
								return;
							}

							// No response data, just empty the array and quit.
							if(d.length < 1){
								self._items = [];
								return;
							}

							var old_first_id = self._items[0].id;
							var new_first_id = d[0].id;

							// First thing to do is remove any old posts by Array.shift() until we match the first response id.
							while(old_first_id != new_first_id && self._items.length > 0){
								self._items.shift();
								old_first_id = self._items.length > 0? self._items[0].id: -1;
							}

							// Loop through each response item, skipping anything that matches.
							for(var i = 0; i < d.length; i++){
								if(self._items[i] && self._items[i].id == d[i].id) continue;

								self._items.push(d[i]);
							}

						})
				}
				, interval_timer: {}
				, start_timer: function(){
					this.interval_timer = $interval(this.update_queue, 1000);
				}
				, stop_timer: function(){
					$interval.cancel(this.interval_timer);
				}
				, push_message: function(){
					Api.save({}, {task:'save', message: this.message}, function(d){ this.message = ''; }.bind(this))
				}
				, __init: function(){
					this._items = Api.post({}, {task: 'load'});
					this.start_timer();
				}
			};

			$scope.messages.__init();

			angular.element('#message').bind('keyup', function(e){
				if(e.keyCode == 13){
					$scope.messages.push_message();
				}
			})
		}])
</script>
</body>
</html>
