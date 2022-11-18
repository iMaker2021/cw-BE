define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auction/order/index' + location.search,
                    // add_url: 'auction/order/add',
                    edit_url: 'auction/order/edit',
                    // del_url: 'auction/order/del',
                    // multi_url: 'auction/order/multi',
                    // import_url: 'auction/order/import',
                    table: 'auction_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', sortable: true, title: __('Id')},
                        {field: 'goods_id', sortable: true, title: __('Goods_id')},
                        {field: 'goods.title', title: __('Goods.title'), operate: 'LIKE'},
                        {field: 'user_id', sortable: true, title: __('User_id')},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'total_score', title: __('Total_score'), sortable: true, operate:'BETWEEN'},
                        {field: 'express_no', title: __('Express_no'), operate:'LIKE'},
                        {field: 'receive_name', title: __('Receive_name'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), operate: false, formatter: Table.api.formatter.status, searchList: {1: __('To be shipped'), 2: __('Shipped')}},
                        {field: 'createtime', title: __('Createtime'), sortable: true, operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
