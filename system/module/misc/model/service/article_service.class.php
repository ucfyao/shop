<?php
/**
 *		文章服务层
 *      [HeYi] (C)2013-2099 HeYi Science and technology Yzh.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.yaozihao.cn
 *      tel:18519188969
 */

class article_service extends service {
	public function _initialize() {
		$this->db = $this->load->table('misc/article');
        $this->category_db = $this->load->table('misc/article_category');
		$this->category_service = $this->load->service('misc/article_category');
	}
	/**
	 * [get_article_by_id 根据id获取文章信息]
	 * @param  [type] $id [查询单条文章id]
	 * @return [type]     [description]
	 */
	public function get_article_by_id($id){
		if((int)$id < 1){
			$this->error = lang('article_not_exist','misc/language');
			return FALSE;
		}
		$result = $this->db->find($id);
		$result['category_ids'] = $this->category_service->get_parents_id($result['category_id']);
		$result['category'] = $this->category_service->get_parents_name($result['category_id']);
		if(!$result){
			$this->error = $this->db->getError();
		}
		return $result;
	}
	
	/**
	 * [edit 编辑文章]
	 * @param [array] $params [规格信息]
	 * @return [boolean]         [返回ture or false]
	 */
	public function edit($params){
		if((int)$params['id'] < 1){
			$this->error = lang('article_not_exist','misc/language');
			return FALSE;
		}
		$data = array();
		$data = $params;
		if($params['thumb']){
			$data['thumb'] =  $params['thumb'];
		}
		runhook('article_edit',$data);
		$result = $this->db->update($data);
    	if($result === false){
			$this->error = $this->db->getError();
    		return FALSE;
    	}else{
    		return TRUE;
    	}
	}
	/**
	 * [delete 删除文章]
	 * @param [array] $params [规格信息]
	 * @return [boolean]         [返回ture or false]
	 */
	public function delete($params){
		if(!$this->is_array_null($params)){
			$this->error = lang('article_not_exist','misc/language');
			return FALSE;
		}
		$data = array();
		$data['id'] = array('IN', $params['id']);
		if(!$this->delete_img(explode(',',$params['id'][0]))){
			$this->error = lang('image_delete_error','misc/language');
			return FALSE;
		}
		$infos = $this->db->where($data)->getField('id,thumb,content',true);
		foreach ($infos AS $info) {
			$this->load->service('attachment/attachment')->attachment('', $info['thumb'],false);
			$this->load->service('attachment/attachment')->attachment('', $info['content']);
		}
		runhook('article_delete',$data);
		$result = $this->db->where($data)->delete();
    	if(!$result){
			$this->error = $this->db->getError();
    		return FALSE;
    	}
    	return TRUE;
	}
	/**
	 * [is_array_null 是否传值]
	 * @param [array] $params [传递的数组]
	 * @return [boolean]         [返回ture or false]
	 */
	public function is_array_null($params){
		if($params['id']['0'] == null){
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * [delete_img 删除文章插件下的图片]
	 * @param [array] $params [文章id]
	 * @return [boolean]         [返回ture or false]
	 */
	public function delete_img($params){
		foreach($params as $key => $value){
			$content = $this->db->where(array('id'=>array('eq',$value)))->getField('content');
			//获取图片全路径
			$path = substr($_SERVER[DOCUMENT_ROOT],0,strlen($_SERVER[DOCUMENT_ROOT])-1);
			preg_match_all("/src=('|\")([^'\"]+)('|\")/", $content,$match);
			//组装路径
			$img_path = array_unique($match);
			$img_path = str_replace('src=','',$img_path[0]);
			for($i=0; $i<count($img_path);$i++){
				$new_path = substr($img_path[$i],1);
				$final_path = substr($new_path,0,-1);
				if(!unlink($path.$final_path)){
					return TRUE;
				}
			}
		}
		return TRUE;
	}
	/**
	 * [add 添加文章]
	 * @param [array] $params [文章信息]
	 * @return [type] [description]
	 */
	public function add($params){
		$data = array();
		$data = $params;
		runhook('article_add',$data);
		$result = $this->db->update($data);
    	if(!$result){
    		$this->error = $this->db->getError();
    		return FALSE;
    	}else{
    		return TRUE;
    	}
	}
	/**
	 * [ajax_edit 修改文章]
	 * @param  [array] $params [修改的数据]
	 * @return [boolean]     [返回更改结果]
	 */
	public function ajax_edit($params){
		runhook('article_add',$params);
		$result = $this->db->update($this->assembl_array($params));
		if(!$result){
    		$this->error = $this->db->getError();
    		return FALSE;
    	}else{
    		return TRUE;
    	}
	}
	/**
	 * [assembl_array 组装数组]
	 * @param  [array] $params [修改的数据]
	 * @return [type] $data     [返回更改结果]
	 */
	public function assembl_array($params){
		if((int)$params['id'] < 1){
			$this->error = lang('_param_error_');
			return FALSE;
		}
		$data = array();
		$data_key = array_keys($params);
		$data_value = array_values($params);
		foreach($data_key as $key => $value){
			$data[$value] = $data_value[$key];
		}
		return $data;
	}
	/**
	 * [get_parent_category 获取多条分类]
	 * @param  [array] $params [文章id和title]
	 * @return [boolean]     [返回更改结果]
	 */
	public function get_parent_category($params){
		$result = $this->category_db->where(array('id'=>array('IN',$params)))->getField('name',TRUE);
		if(!$result){
			$this->error = $this->logic->error;
			return FALSE;
		}
		return $result;
	}
	/**
	* [fieldinc 增加字段值]
	* @param integer $id    id
	* @return [boolean]     [返回更改结果]
	*/
	public function hits($id){
		return $this->db->where(array('id' => $id))->setInc('hits');
	}
}