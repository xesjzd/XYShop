<?php

namespace App\Http\Controllers\Admin\Shop;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Shop\ShopMenuRequest;
use App\Models\ShopMenu;
use Illuminate\Http\Request;

class ShopMenuController extends BaseController
{
    public function getIndex()
    {
        $title = '菜单列表';
        $list = ShopMenu::orderBy('sort','asc')->orderBy('id','asc')->get();
        $tree = app('com')->toTree($list,'0');
        $treeHtml = $this->toTreeHtml($tree);
        return view('admin.shopmenu.index',compact('treeHtml','title'));
    }
    
    // 树形菜单 html
    private function toTreeHtml($tree)
    {
        $html = '';
        foreach ($tree as $v) {
            // 用level判断层级，最好不要超过四层，样式中只写了四级
            $level = count(explode(',', $v['arrparentid']));
            $disStr = $v['is_show'] ? "<span class='text-success'>是</span>" : "<span class='text-danger'>否</span>";
            // level < 4 是为了不添加更多的层级关系，其它地方不用判断，只是后台菜单不用那么多级
            if ($level < 4) {
                 $html .= "<tr>
                    <td>".$v['sort']."</td>
                    <td>".$v['id']."</td>
                    <td><span class='level-".$level."'></span>".$v['name']."<div data-url='/console/shopmenu/add/".$v['id']."' class='glyphicon glyphicon-plus curp add_submenu btn_modal' data-title='添加菜单' data-toggle='modal' data-target='#myModal'></div></td>
                    <td>".$v['url']."</td>
                    <td>".$disStr."</td>
                    <td><div data-url='/console/shopmenu/edit/".$v['id']."' class='btn btn-xs btn-info glyphicon glyphicon-edit btn_modal' data-title='修改菜单' data-toggle='modal' data-target='#myModal'></div> <a href='/console/shopmenu/del/".$v['id']."' class='btn btn-xs btn-danger glyphicon glyphicon-trash confirm'></a></td>
                    </tr>";
            }
            else
            {
                 $html .= "<tr>
                    <td>".$v['sort']."</td>
                    <td>".$v['id']."</td>
                    <td><span class='level-".$level."'></span>".$v['name']."</td>
                    <td>".$v['url']."</td>
                    <td>".$disStr."</td>
                    <td><div data-url='/console/shopmenu/edit/".$v['id']."' class='btn btn-xs btn-info glyphicon glyphicon-edit btn_modal' data-title='修改菜单' data-toggle='modal' data-target='#myModal'></div> <a href='/console/shopmenu/del/".$v['id']."' class='btn btn-xs btn-danger glyphicon glyphicon-trash confirm'></a></td>
                    </tr>";
            }
            if ($v['parentid'] != '')
            {
                $html .= $this->toTreeHtml($v['parentid']);
            }
        }
        return $html;
    }

    /**
     * 添加菜单模板
     * @param  Request $request [description]
     * @param  integer $pid     [父栏目id，默认为0，即为一级菜单]
     * @return [type]           [description]
     */
    public function getAdd(Request $request,$pid = 0)
    {
        $title = '添加菜单';
    	return view('admin.shopmenu.add',compact('pid','title'));
    }
    /**
     * 添加菜单提交数据
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function postAdd(ShopMenuRequest $request)
    {
    	$data = request('data');
    	ShopMenu::create($data);
        app('com')->updateCache(new ShopMenu(),'shopmenuCache');
        return $this->ajaxReturn(1,'添加菜单成功',url('/console/shopmenu/index'));
    	// return redirect('')->with('message', '');
    }
    /**
     * 修改菜单，当修改父级菜单的时候level要相应的进行修改
     * @param  integer $id [要修改的菜单ID]
     * @return [type]      [description]
     */
    public function getEdit($id = 0)
    {
        $title = '修改菜单';
        $info = ShopMenu::findOrFail($id);
        $list = ShopMenu::orderBy('sort','asc')->get();
        $tree = app('com')->toTree($list,'0');
        $treeSelect = app('com')->toTreeSelect($tree,$info->parentid);
        return view('admin.shopmenu.edit',compact('title','info','treeSelect'));
    }
    public function postEdit(ShopMenuRequest $res,$id)
    {
        $data = $res->input('data');
        ShopMenu::where('id',$id)->update($data);
        app('com')->updateCache(new ShopMenu(),'shopmenuCache');
        return $this->ajaxReturn(1,'修改菜单成功',url('/console/shopmenu/index'));
        // return redirect('/console/shopmenu/index')->with('message', '修改菜单成功！');
    }
    /**
     * 删除菜单及下属子菜单，取出当前菜单ID下边所有的子菜单ID（添加修改的时候会进行更新，包含最小是自身），然后转换成数组格式，指进行删除，然后更新菜单
     * @param  [type] $id [要删除的菜单ID]
     * @return [type]     [description]
     */
    public function getDel($id)
    {
        $info = ShopMenu::findOrFail($id);
        $arr = explode(',', $info->arrchildid);
        ShopMenu::destroy($arr);
        app('com')->updateCache(new ShopMenu(),'shopmenuCache');
        return redirect('/console/shopmenu/index')->with('message', '删除菜单成功！');
    }
}
