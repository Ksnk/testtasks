<?php
/**
 * Helper-функции
 */

/**
 * гарантия от повторного старта сессии
 */
function sessionstart()
{
    static $started = false;
    if (!$started) session_start();
    $started = true;
}

/**
 * минус лишние проверки
 * @param $rec
 * @param $disp
 * @param string $default
 * @return string
 */
function val($rec,$disp,$default=''){
    $x=explode('|',$disp);
    $v=$rec;
    foreach($x as $xx){
        if(is_object($v)){
            if(property_exists($v,$xx)){
                $v=$v->$xx;
            } else {
                $v=$default;
                break;
            }
        } elseif(isset($v[$xx])){
            $v=$v[$xx];
        } else {
            $v=$default;
            break;
        }
    }
    return $v;
}

/**
 * формопостроитель на коленке.
 */
function createField($data,$k){
    static $id_cnt=100;
    $id_cnt++;
    // в этом месте мог бы быть нормальный формопостроитель :(
    $v=val($data,$k);
    if(!$v) return '';
    $result='';
    $type=val($v,'type','text');
    if($type=='textarea'){
        $result.= '<label for="id'.$id_cnt.'">'.val($v,'title',$k);
        if(val($v,'require'))
            $result.= '<sup class="red"> * </sup>';
        $result.= '</label>
<div class="control">
<textarea id="id'.$id_cnt.'" name="'.$k.'">'.htmlspecialchars(val($v,'value')).'</textarea>';
        if(val($v,'error')){
            $result.= '<span class="error">'.val($v,'error').'</span>';
        }
        $result.='</div><div class="clear"></div>';

    } else {
        $result.= '<label for="id'.$id_cnt.'">'.val($v,'title',$k);
        if(val($v,'require'))
            $result.= '<sup class="red"> * </sup>';
        $result.= '</label>
<div class="control"><input id="id'.$id_cnt.'" type="'.val($v,'type','text').'" name="'.$k.'" value="'.htmlspecialchars(val($v,'value')).'">';
        if(val($v,'error')){
            $result.= '<span class="error">'.val($v,'error').'</span>';
        }
        $result.='</div><div class="clear"></div>';
    }
    return $result;
}
