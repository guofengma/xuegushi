<?php
/**
 * Controller show
 * 专栏 文章 controller
 */

namespace App\Http\Controllers\ZhuanLan;

use DB;
use Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Http\Request;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class PostController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth');
    }

    /**
     * 写文章
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function write(){
        if (Auth::guest()){
            return redirect('/login');
        }
        $zhuanlans = DB::table('dev_zhuanlan')
            ->where('creator_id',Auth::user()->id)
            ->get();
        return view('zhuan.post.create')
            ->with('is_has',$this->isHasZhuanlan())
            ->with('zhuans',$zhuanlans)
            ->with('site_title','写文章');
    }

    /**
     * c创建文章
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request){
        $zl_id = $request->input('zhuanlan');
        $zhuanlan_id = $zl_id == -1 ? $this->getUserZhuanId(Auth::user()->id) : $zl_id;
        $data = [];
        $data['creator_id'] = Auth::user()->id;
        $data['created_at'] = date('Y-m-d H:i:s',time());
        $data['updated_at'] = date('Y-m-d H:i:s',time());
        $data['zhuanlan_id'] = $zhuanlan_id;
        $data['title'] = $request->input('title');
        $data['topic']=$request->input('topic');
        $data['content']=$request->input('content');
        $data['cover_url']=$request->input('cover_image');
        $data['status'] = $request->input('status');
        $_id = DB::table('dev_post')->insertGetId($data);
        $res = [];
        if($_id){
            $_data = [];
            $_data['zhuanlan_id'] = $zhuanlan_id;
            $_data['post_id'] = $_id;
            $_data['creator_id'] = Auth::user()->id;
            $_data['created_at'] = date('Y-m-d H:i:s',time());
            DB::table('dev_post_relation')->insertGetId($_data);
            $res['id'] = $_id;
            $res['status'] = 'success';
        }
        return response()->json($res);
    }

    /**
     * update 文章
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request){
        $zl_id = $request->input('zhuanlan');
        $zhuanlan_id = $zl_id == -1 ? $this->getUserZhuanId(Auth::user()->id) : $zl_id;
        $id = $request->input('id');
        $data = [];
        $data['updated_at'] = date('Y-m-d H:i:s',time());
        $data['title'] = $request->input('title');
        $data['topic']=$request->input('topic');
        $data['content']=$request->input('content');
        $data['cover_url']=$request->input('cover_image');
        $data['status'] = $request->input('status');
        $data['zhuanlan_id'] = $zhuanlan_id;
        $_res = DB::table('dev_post')->where('id',$id)->update($data);
        $res = [];
        if($_res){
            $res['status'] = 'success';
        }
        return response()->json($res);
    }

    /**
     * 删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request){
        $id = $request->input('id');
        $_res = DB::table('dev_post')->where('id',$id)->update([
            'status' => 'delete',
            'updated_at' => date('Y-m-d H:i:s',time())
        ]);
        $res = [];
        if($_res){
            $res['status'] = 'success';
        }
        return response()->json($res);
    }

    /**
     * 恢复
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request){
        $id = $request->input('id');
        $_res = DB::table('dev_post')->where('id',$id)->update([
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s',time())
        ]);
        $res = [];
        if($_res){
            $res['status'] = 'success';
        }
        return response()->json($res);
    }
    /**
     * 详情页
     * @param $id
     * @return $this
     */
    public function show($id){
        $data = DB::table('dev_post')
            ->where('dev_post.id',$id)
            ->leftJoin('users','users.id','=','dev_post.creator_id')
            ->leftJoin('dev_zhuanlan','dev_zhuanlan.id','=','dev_post.zhuanlan_id')
            ->select('dev_post.*','users.name as user_name','users.avatar','dev_zhuanlan.alia_name as zhuan_alia_name',
                'dev_zhuanlan.about','dev_zhuanlan.avatar as zhuan_avatar','dev_zhuanlan.name as zhuan_name')
            ->first();
        if(isset($data) && $data){
            DB::table('dev_post')->where('id',$data->id)->increment("pv_count");
            return view('zhuan.post.show')
                ->with('post',$data)
                ->with('is_has',$this->isHasZhuanlan())
                ->with('site_title',$data->title);
        }else{
            return view('errors.404')->with('record_id',$id)->with('record_name','文章');
        }
    }
    /**
     * 编辑
     * @param $id
     * @return mixed
     */
    public function edit($id=null){
        $data = DB::table('dev_post')
            ->where('dev_post.id',$id)
            ->first();
        if(isset($data) && $data){
            $zhuanlans = DB::table('dev_zhuanlan')
                ->where('creator_id',Auth::user()->id)
                ->get();
            return view('zhuan.post.edit')
                ->with('post',$data)
                ->with('zhuans',$zhuanlans)
                ->with('is_has',$this->isHasZhuanlan())
                ->with('site_title','编辑-'.$data->title);
        }else{
            return view('errors.404')->with('record_id',$id)->with('record_name','文章');
        }
    }
    /**
     * 判断当前用户是否注册过专栏
     * @return bool
     */
    public function isHasZhuanlan(){
        if (!Auth::guest()){
            $data = DB::table('dev_zhuanlan')->where('creator_id',Auth::user()->id)->count();
        }else{
            $data = 0;
        }
        if($data>0){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 返回用户的专栏id
     * @param $id
     * @return mixed
     */
    public function getUserZhuanId($id){
        $data = DB::table('dev_zhuanlan')->where('creator_id',$id)->first();
        if(isset($data) && $data){
            return $data->id;
        }else{
            return -1;
        }
    }
}