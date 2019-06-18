<?php 

namespace Rvwoens\Former\fields;
use Laravel,Bootstrapper,Former,Rvwoens\Former\Cos, Rvwoens\Former\vars, Rvwoens\Former\imgscale, \Form, \Input, \Url, \Asset, \Log ;

// imageurl - show image. READONLY field
class imgurlfield extends editablefield {

	public function getbaseinput($row,&$gridsize) {

		$divstyle='';
		$img=e($this->val($row));
		if (!$img && isset($this->def['defimg'])) {
			$img=imgscale::path2url($this->def['defimg']);
		}
		$rv="<div class=\"col-md-$gridsize\">";
		if (isset($this->def['upload'])) {
			$rv .= $this->getUploadForm();
			$divstyle=" style='margin:10px;text-align:center;' ";
		}
		if ($img)
			$rv .= $this->showCurrentImage($divstyle,$img);
		$rv.="</div>";	// div gridsize
		return $rv;
	}
	public function display($row=null) {
		if (isset($this->def['html']))
			return vars::v($this->def['html']);
		$height=intval(COS::ifset($this->def['listheight'],24));
		return "<img src='".e($this->val($row))."' style='height:{$height}px'>";
	}

	//return true if the fields does a fileupload
	public function hasFileUpload() {
		return isset($this->def['upload']);	// if we have upload, we need a fileuploadform
	}
	public function getUpdateSql($do) {
 		if (isset($this->def['upload'])) {
			//copy our file!
			//Log::info("thisfile: ".print_r($thisfile,true));
			if (Input::hasFile($this->formfieldname)) {
				$thisfile=Input::file($this->formfieldname);
				if ($thisfile->isValid()) { 	// L3: is_array($thisfile) && isset($thisfile['error'])) {
					// success
					$orgname=$thisfile->getClientOriginalName();  //->getClientOriginalExtension();
					$orgext=strtolower(pathinfo($orgname,PATHINFO_EXTENSION));
					$basefile=$this->formfieldname.time().'.'.$orgext;
					// upl is a symfony\httpfoundation\file\file object!
					$upldir=public_path().$this->def['upload'];
					$file=$upldir.'/'.$basefile;

					$thisfile->move($upldir, $basefile);
					//$upl=Input::upload($this->formfieldname,
					//	path('public').$this->def['upload'],$basefile);
					Log::info("uploaded $orgname to ".$file);

					if (class_exists('\s3fy\file',true)) {
						// ABSORB the sucker..
						$url = \s3fy\file::absorb($file);
					}
					else
						$url= URL::Asset($this->def['upload'].'/'.$basefile);
					//imgscale::scale($file,array('h'=>230));  // scale ON DEMAND
					return array($this->name=>$url);
				}
				else {
					/// error uploading
					// 1=ini_size, 2=form_size 3=partial 4=No file 6=no temp 7=cant write 8=extension
					;
				}
			}		
		}
		// even for NON-updateable fields there can be an updatesql in the def
		if ( ($rv=$this->getDefUpdateSql($do))!==null)
			return $rv ? $rv : array();	// overridden! (if empty-> do not update)
		return array();
	}


	/**
	 * @param $divstyle
	 * @param $rv
	 * @param $filetype
	 * @param $img
	 * @param $trans
	 * @param $height
	 * @return string
	 */
	public function showCurrentImage($divstyle,$img) {
		$height=intval(COS::ifset($this->def['showheight'],COS::ifset($this->def['height'],230)));
		$width=intval(COS::ifset($this->def['width'],0));	// 0 = scale, not crop
		$trans=COS::ifset($this->def['trans'],'former_def');	// s3fy transformation (w400h300 etc)

		$filetype=strtolower( strrchr($img,'.') );
		if (COS::ifset($this->def['forceimage'],'N')=='Y')
			$filetype='.jpg';

		$rv = "<div $divstyle>";
		switch ($filetype) {
		case '.pdf':
			if (class_exists('\s3fy\file',true)) {
				$img=\s3fy\file::get($img);
			}
			$rv .= "<a target=_blank href='$img'><img src='/img/pdf-icon-48x48.png'> ".
				   __('former::elements.downloaddocument')."</a>";
			break;
		case '.jpg':
		case '.png':
		case '.jpeg':
		case '.gif':
			if (class_exists('\s3fy\file',true)) {
				$img=\s3fy\picture::get($img, $trans);
			}

			$rv .= "<img src='$img' style='border: 1px solid #BBB;padding: 5px;background-color: white;height:${height}px'>";
			break;
		default:
			if (class_exists('\s3fy\file',true)) {
				$img=\s3fy\file::get($img);
			}
			$rv .= "<a target=_blank href='".$img."'>".trans('former::elements.downloaddocument')."</a>";
			break;
		}
		$rv .= "</div>";
		return $rv;
	}

	/**
	 * @param $rv
	 * @return string
	 */
	public function getUploadForm() {
		// UPLOAD FORM
		//$fsize=array_get($this->def,'size','span6');
		//$field=$fsize.'_file';
		//$rv.=Form::$field($this->formfieldname,array('id'=>'bfiledrop'));
		// TF-uploader!
		$rv = '
				<input type="hidden" name="_xhr" id="_xhr" value="0">
				<div class="input" id="binary-filedrop">
					<div class="filedrop-container">
						<div class="filedrop-dropZone small">
							<span class="filedrop-message">'.trans('former::elements.dropfileorselect').'</span>
							<input class="filedrop-input" type="file" name="'.$this->formfieldname.'" id="id_binary">
						</div>
						<div class="filedrop-progress">
							<div class="filedrop-progressBar">
								<div><div class="filedrop-progressBarInner">
								</div></div>
							</div><span class="filedrop-progressBarMessage">uploading...</span>
						</div>
					</div>
				</div>';

		$rv .= $this->getJs();
		return $rv;
	}

	/**
	 * @param $rv
	 * @return string
	 */
	public function getJs() {
		$rv = "<script type=\"text/javascript\">

				$(function() {
				    var displayError=function(id, message) {
				        var element = $('#' + id);
				        element.after('<ul class=\"errorlist error-message\"><li>' + message + '</li></ul>')
				        $('.filedrop-dropZone').addClass('error');
				    }
		            var dropped = false;
		            $('#card-form').submit(function() {
                		var haserror = false;
               			$('#binary-filedrop').siblings('.errorlist').remove();	// remove old errors
						var has_file = $('#binary-filedrop').filedrop('hasFile'); \n";
		// generate validation codes
		if (isset($this->def['validate'])) {
			foreach ($this->def['validate'] as $val) {
				if (strpos($val, ':')!==false)
					list($set, $sval) = explode(":", $val);
				else
					$set = $val;
				switch ($set) {
				case 'max':
					$maxsize = round($sval/1024, 1)."Mb";    // mb
					if ($sval>=1024*1024)
						$maxsize = round($sval/1024/1024, 1)."Gb";
					$rv .= "
						                if(window.File && has_file) {
						                    var file = $('#binary-filedrop').filedrop('getFile');
						                    if(file.files) {
						                        if(file.files[0].size > ".($sval*1024).") {
						                            displayError('binary-filedrop', 'File is too big - Maximum size is ".$maxsize.".')
						                            haserror = true;
						                        }
						                    }
						                }
									";
					break;
				case 'mimes':
					$fexts = '';
					foreach (explode(',', $sval) as $ext)
						$fexts .= "'".$ext."',";
					$rv .= "\n
										if(window.File && has_file) {
				                    		var file = $('#binary-filedrop').filedrop('getFile');
			                    			if(file.files) {
												var name=file.files[0].name;
												var re = /(?:\.([^.]+))?$/;
												var ext=re.exec(name)[1];
												var allowedexts=[".$fexts."];
												var allow=false;
			                 					for (var i in allowedexts) {
													if (ext && ext.toUpperCase() === allowedexts[i].toUpperCase())
														allow=true;
												}
												if (!allow) {
						                            displayError('binary-filedrop', 'File type is not valid. Only ".
						   $sval." files allowed');
						                            haserror = true;
						                        }
			 								}
										}\n";
					break;
				case 'required':
					$rv .= "
				                		if (!has_file) {
				                    		displayError('binary-filedrop', 'This field is required.');
				                    		haserror = true;
				                		}\n";
					break;
				}
			}
		}
		$rv .= "
			            if (haserror) {
			                return false;
			            }
						if (has_file) {
							$('input[name=_xhr]').val('1');	// mark as xhr post
							$('#binary-filedrop').filedrop('submit');
		                	return false;
						}
						return true;	// normal submit without uploading
		            });

					// initialise the field
		            $('#binary-filedrop')
						.filedrop({
			                initialMessage: '".trans('former::elements.dropfileorselect')."',
				            //inputName: 'binary',
				            inputId: 'id_binary',
			                inputName: '".$this->formfieldname."',
			                //inputId: 'bfiledrop'
			            })
			            .bind('filedropdrop', function(e, options) {
			                dropped = true;
			            })
			            .bind('filedropdone', function(e, options) {
							// uploading READY
			                var data = options.result;
			                if (data.redirect) {
								//alert('redirect '+data.redirect);
			                    window.location = data.redirect;
			                } else if (data.errors) {
								// not used. Uses redirect with flash
			                    if (data.errors.".$this->formfieldname.") {
			                        displayError('binary-filedrop', data.errors.".$this->formfieldname."[0]);
			                    }
			                } else {
			                    displayError('binary-filedrop', 'An unexpected error has occurred.');
			                }
			            })
			            .bind('filedropfail', function(e, data) {
			                displayError('binary-filedrop', 'An unexpected error has occurred.');
			            });
		        });
		    </script>";
		return $rv;
	}

}