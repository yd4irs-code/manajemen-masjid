<?php
trait TraitForm {
	private function generateCompleteForm($arr,$setting=[]){
		$default = [
			'id'	=> '',
			'title'	=> '',
			'color'	=> 'box-info',
			'index'	=> 'no-index',
			'info'	=> false,
			'open'	=> true
		];
		$set	= array_merge($default,$setting);
		$icon	= $set['open']?'fa-minus':'fa-plus';
		$class	= $set['color'].($set['open']?'':' collapsed-box');
		$form	= '
			<form method="post" class="form">
			<div class="box '.$class.'">
				<div class="box-header with-border">
					<h3 class="box-title">'.$set['title'].'</h3>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa '.$icon.'"></i></button>
					</div>
				</div>
				<div class="box-body">';
					foreach($arr as $k => $v){
						$required	= '';
						if(array_key_exists('required', $v)){
							$required	= $v['required']?' required':'';
							unset($v['required']);
						}
						if($v['type']=='select'){
							$reverse	= true;
							if(array_key_exists('rev', $v)){
								$reverse	= $v['rev'];
							}
							$form	.= '
							<div class="input-group">
								<span class="input-group-addon">'.$k.'</span>
								<select class="form-control"';
									$form	.= array_key_exists('name', $v)?' name="'.$v['name'].'"':' name="'.$k.'"';
									$form	.= $required.'>'.$this->generateOptionSelect($v['value'],$v['arr'],$reverse).
								'</select>'.
							'</div>';
						}
						else{
							$form	.= '
							<div class="input-group">
								<span class="input-group-addon">'.$k.'</span>
								<input class="form-control"';
								$addon	= '';
								if(!array_key_exists('name', $v))$form.=' name="'.$k.'"';
								foreach($v as $kr => $vr){
									if($kr=='required')$form	.= " required";
									else if($kr=='addon') $addon = '<span class="input-group-addon">'.$vr.'</span>';
									else $form	.= " $kr=\"$vr\"";
								}
								$form	.= $required.'>'.$addon.
							'</div>';
						}
					}
					$form .='
					<div class="input">
						'.($set['info']?'<small>'.nl2br($set['info']).'</small>':'').'
						<input type="hidden" name="formId" value="'.$set['id'].'">
						<input type="hidden" name="index" value="'.$set['index'].'">
					</div>
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> simpan</button>
				</div>
			</div>
			</form>
		';
		return $form;
	}

	private function generateTextForm($arr,$setting=[],$required=true){
		$form	= [];
		foreach($arr as $k => $v){
			$form[$k]	= [
				'type'		=> 'text',
				'maxlength'	=> 100,
				'value'		=> $v,
				'required'	=> $required
			];
		};
		return $this->generateCompleteForm($form,$setting);
	}

	private function formPrayTimesAdjust($arr,$required=false){
		$form	= '';
		$req	= $required?'required':'';
		foreach($arr as $k => $v){
			$form	.= '
			<div class="input-group">
				<span class="input-group-addon">'.$k.'</span>
				<input 
					name	="'.$k.'" 
					type	="text" 
					class	="form-control" 
					maxlength	="100" 
					value	="'.$v.'"
					'.$req.'>
			</div>
			';
		}
		return $form;
	}

	private function generateOptionSelect($selected,$arr,$reverse=true){
		$opt	= '';
		foreach($arr as $k => $v){
			if($reverse){
				$sel	= $v==$selected?'selected':'';
				$opt	.= "<option value=\"$v\" $sel>$k</option>";
			}
			else{
				$sel	= $k==$selected?'selected':'';
				$opt	.= "<option value=\"$k\" $sel>$v</option>";
			}
		}
		return $opt;
	}

}
