<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\Category;
use app\admin\model\auction\Goods;
use app\common\model\GoodsPriceLog;
use app\common\model\Message;
use think\Db;
use think\Exception;

/**
 * 文章接口
 */
class Auction extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 拍卖分类
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function category()
    {
        $category = Category::field(['id','name','image'])->withCount(['goods' => function($query){
            $query->where('status', 1);
        }])->where(['type' => 'auction', 'status' => 'normal'])->order('weigh desc,id desc')->select();
        foreach ($category as &$value){
            if($value['image']) $value['image'] = cdnurl($value['image'], true);
        }
        if (!is_array($category)) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $category);
        }
    }

    /**
     * 拍卖商品列表
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goods()
    {
        $cateId = $this->request->get('cate_id', 0);
        $minPrice = $this->request->get('min_price', 0);
        $maxPrice = $this->request->get('max_price', 0);
        $orderType = $this->request->get('order_type', 0);
        $where['status'] = ['eq', 1];
        $where['end_time'] = ['gt', time()];
        if($cateId) $where['category_id'] = ['eq', $cateId];
        if($minPrice) $where['now_price'] = ['gt', $minPrice];
        if($maxPrice) $where['now_price'] = ['lt', $maxPrice];
        switch ((int)$orderType){
            case 0:
                $order = 'sort desc';
                break;
            case 1:
                $order = 'end_time asc';
                break;
            case 2:
                $order = 'end_time desc';
                break;
            case 3:
                $order = 'now_price asc';
                break;
            case 4:
                $order = 'now_price desc';
                break;
            default:
                $order = 'sort desc';
        }
        $list = Goods::field(['id','title','images','category_id', 'start_price', 'begin_time', 'end_time'])->where($where)->order($order)->paginate(10)->toArray();
        foreach ($list['data'] as &$value){
            if($value['images']){
                $images = explode(',', $value['images']);
                foreach ($images as &$val){
                    $val = cdnurl($val, true);
                }
                $value['images'] = $images;
                $value['categories'] = \app\common\model\Category::where('id', $value['category_id'])->value('name');
            }
        }
        if (!is_array($list)) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $list);
        }
    }

    /**
     * 文章详情
     * @ApiMethod (GET)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail()
    {
        $id = $this->request->get('id', 0);
        if(!$id) $this->error('参数错误');
        $detail = Goods::with(['price_log' => function($query){
            $query->field('id,price,goods_id,user_id,createtime')->with(['user'])->order('price desc');
        }])->field(['id','title','images', 'category_id', 'content', 'start_price', 'begin_time', 'end_time'])->find($id);
        if(!$detail) $this->error('商品不存在');
        if($detail['images']){
            $images = explode(',', $detail['images']);
            foreach ($images as &$val){
                $val = cdnurl($val, true);
            }
            $detail['images'] = $images;
        }
        $detail['price_log'] = GoodsPriceLog::field('id,user_id,price,createtime')->with(['user'])->select();
        $where['status'] = 1;
        $where['category_id'] = $detail['category_id'];
        $data = Goods::field(['id','title','images', 'start_price', 'begin_time', 'end_time'])->where($where)->where('end_time', '>', time())->order('id desc')->limit(0,2)->select();
        if($data){
            foreach ($data as &$value){
                if($value['images']){
                    $images = explode(',', $value['images']);
                    foreach ($images as &$val){
                        $val = cdnurl($val, true);
                    }
                    $value['images'] = $images;
                }
            }
        }

        $detail['likes'] = $data;
        //$detail['content'] = htmlentities($detail['content']);
        if (!is_object($detail)) {
            $this->error(__('Get data failed'));
        } else {
            $this->success(__('Get data success'), $detail);
        }
    }

    /**
     * 拍卖出价
     * @ApiMethod (POST)
     */
    public function offer()
    {
        $goods_id = $this->request->param('goods_id');
        $price = $this->request->param('price');
        if(!$goods_id) $this->error(__('Goods id can not be empty'));
        $user = $this->auth->getUserinfo();
        Db::startTrans();
        try{
            // 添加悲观锁 防止并发出价最新价格有误
            $goods = Goods::lock(true)->find($goods_id);
            if(empty($goods)){
                Db::rollback();
                $this->error(__('Goods id does not exist'));
            }
            if(!$goods['status']){
                Db::rollback();
                $this->error(__('The goods has been taken off the shelves'));
            }
            if(time() < $goods['begin_time']){
                Db::rollback();
                $this->error(__('Auction not started'));
            }
            if(time() > $goods['end_time']){
                Db::rollback();
                $this->error(__('Auction closed'));
            }
            if($price <= $goods['now_price']){
                Db::rollback();
                $this->error(__('The price must be greater than the current price'));
            }
            if(strrpos(($price - $goods['now_price']), '.') !== false){
                Db::rollback();
                $this->error(__('Markup range must be an integer'));
            }
            if(($user['score'] - $user['lock_score']) < $price){
                Db::rollback();
                $this->error(__('Insufficient points'));
            }
            //最高出价检测
            $maxUser = GoodsPriceLog::where('goods_id', $goods->id)->order('price', 'desc')->find();
            if($maxUser && $maxUser->user_id == $this->auth->id) {
                Db::rollback();
                $this->error(__('You are already the highest price'));
            }
            //拍卖价格更新通知其他参与拍卖人员
            $msgIds = GoodsPriceLog::where('goods_id', $goods->id)->where('user_id', '<>', $this->auth->id)->column('user_id');
            $msgData =[];
            foreach ($msgIds as $id){
                $msgData[] = [
                    'user_id' => $id,
                    'content' => '您參與的拍賣品 '.$goods->title.'競拍最新價格為 '.$price
                ];
            }
            if($msgData) {
                $msgModel = new Message();
                $msgModel->saveAll($msgData);
            }

            //冻结竞拍最高价用户积分
            \app\common\model\User::lockScore($price, $this->auth->id);
            //如果有前最高价解冻前最高价用户积分
            if($maxUser) \app\common\model\User::lockScore(-$maxUser->price, $maxUser->user_id);
            $data = [
                'goods_id' => $goods_id,
                'user_id' => $this->auth->id,
                'price' => $price,
            ];
            $log = GoodsPriceLog::create($data, true);
            if(!$log){
                Db::rollback();
                $this->error('Bid Failed');
            }
            $result = $goods->save(['now_price' => $price]);
            if($result === false) {
                Db::rollback();
                $this->error('Bid Failed');
            }
            Db::commit();
            $this->success(__('Bid successful'));
        } catch (Exception $exception){
            Db::rollback();
            $this->error($exception->getMessage());
        }
    }

    /**
     * 发布拍卖商品
     * @ApiMethod (POST)
     */
    public function add_goods()
    {
        $this->request->filter(['strip_tags', 'trim']);
        $params = $this->request->param();
        $user = $this->auth->getUser();
        if($user['level'] !== 2)  $this->error(__('Your account does not have permission to publish. Please contact the administrator'));
        if(!isset($params['title']) || empty($params['title'])) $this->error(__('Title can not empty'));
        if(!isset($params['category_id']) || empty($params['category_id'])) $this->error(__('Cate can not empty'));
        if(!isset($params['start_price']) || empty($params['start_price'])) $this->error(__('Start_price can not empty'));
        if(!is_numeric($params['start_price'])) $this->error(__('Start_price must be an numeric'));
        if(!isset($params['images']) || empty($params['images'])) $this->error(__('Images can not empty'));
        if(!is_array($params['images'])) $this->error(__('Images must be an array'));
        if(count($params['images']) > 5) $this->error(__('Upload no more than 5 images'));
        if(!isset($params['begin_time']) || empty($params['begin_time'])) $this->error(__('Begin_time can not empty'));
        if(!isset($params['end_time']) || empty($params['end_time'])) $this->error(__('End_time can not empty'));
        if(time() > strtotime($params['end_time']))  $this->error(__('The end time cannot be less than the current time'));
        if(strtotime($params['begin_time']) >= strtotime($params['end_time']))  $this->error(__('The end time cannot be less than the begin time'));
        $params['now_price'] = $params['start_price'];
        $params['user_id'] = $this->auth->id;
        $params['images'] = implode(',', $params['images']);
        $result = model('app\admin\model\auction\Goods')->allowField(true)->save($params);
        if ($result === false) {
            $this->error(__('Publishing failed'));
        }
        $this->success(__('Publishing successful'));
    }

}
