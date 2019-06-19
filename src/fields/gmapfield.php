<?php 

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Rvwoens\Former\Cos, Rvwoens\Former\Vars,\exception, \Input, \Form;

// base field class
class gmapfield extends editablefield {
	
	public function getbaseinput($row) {
		$rv='';$style='';
		$height=COS::ifset($this->def['height'],400);
		$searchbar=COS::boolval($this->def['searchbar'],false);
		$gridsize=array_get($this->def,'size','12');
		
		$latfield=$this->formfieldname;
		$lngfield=COS::ifset($this->def['lngfield'],'lngfield_NOT_DEFINED');
		$radiusfield=COS::ifset($this->def['radiusfield'],'');
		$initzoom=(int)COS::ifset($this->def['initzoom'],10);
		$address=vars::v(COS::ifset($this->def['address'],''));

		$baselatlng=vars::v(COS::ifset($this->def['baselatlng'],''));
		$baselat=0;$baselng=0;
		if ($baselatlng) {
			$abase = explode(',', $baselatlng);
			if (count($abase) >= 2) {
				$baselat = floatval($abase[ 0 ]);
				$baselng = floatval($abase[ 1 ]);
			}
		}
		$baseicon=vars::v(COS::ifset($this->def['baseicon'],'')); // purple_MarkerM.png see /build/img/former/GoogleMapsMarkers

		$lat=e($this->val($row));
		$lng=Input::old($lngfield, is_object($row)?$row->{$lngfield}:0);
		$lat=floatval($lat);
		$lng=floatval($lng);
		if (abs($lat)<0.1 && abs($lng)<0.1) {
			if ($baselat && $baselng) {
				$lat = $baselat;
				$lng = $baselng;
			}
		}

		$rv='<script type="text/javascript" src="/build/js/gmapfield.js"></script>';
		$rv.="<div class='col-md-$gridsize' style='${style}'>";
			if ($searchbar) {
				$rv.='<nav class="navbar navbar-default" style="margin-bottom:0" >
				 		<div class="container-fluid navbar-form">
							<div class="col-md-3"><input type="text" id="gmapsearch" class="form-control" placeholder="Enter location"></div>
							<div class="col-md-6">
								<button href="#" id="gmapmyloc" class="btn btn-default"><span class="glyphicon glyphicon-map-marker"></span> my location</button>
								';
								if (false && $lat && $lng) {
									$rv.='
										<li><a href="#" id="gmapvenueloc"><span class="glyphicon glyphicon-map-marker"></span> venue location</a></li>';
								}
								if ($address) {
									$rv.='
										<button href="#" id="gmapvenueaddress" class="btn btn-default"><span class="glyphicon glyphicon-arrow-down"></span> search venue address</button>';
								}
								$rv.='
							</div>
							<div class="col-md-3">
								<div id="curlatlong" style="line-height:32px;font-size:12px;float:right"></div>
							</div>
						</div>
					 </nav>';
			}
			$rv.="<div class='google-maps' id='mcanvas' style='border:1px solid #ddd;height:${height}px'>";
			$rv.="</div>";
		$rv.="</div>";
		$rv.="<script type='text/javascript'>
			jQuery(function($) {
			
				gmap.setMarkerfields('$latfield','$lngfield');
				gmap.setAddress('$address');
				gmap.init($lat,$lng,$initzoom,'$address');\n";

				if ($searchbar) {
					$rv .= "gmap.searchbar(jQuery,$lat,$lng);\n";
				}

				if ($radiusfield) {
					$rv .= "gmap.addRadiusCircle('$radiusfield');\n";
				}

				$rv.="
			});
			</script>";
		$rv.=Form::hidden($latfield,$lat);
		$rv.=Form::hidden($lngfield,$lng);
		return $rv;
	}
	public function getUpdateSql($do) {
		$rv=parent::getUpdateSql($do);
		if (isset($this->def['lngfield'])) 
			$rv[$this->def['lngfield']] = Input::get($this->def['lngfield']);
		return $rv;
	}

}