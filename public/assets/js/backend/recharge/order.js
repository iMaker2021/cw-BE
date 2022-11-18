define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'recharge/order/index' + location.search,
                    // add_url: 'recharge/order/add',
                    // edit_url: 'recharge/order/edit',
                    // del_url: 'recharge/order/del',
                    // multi_url: 'recharge/order/multi',
                    // import_url: 'recharge/order/import',
                    table: 'recharge_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', sortable: true, title: __('Id')},
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), sortable: true, operate:'BETWEEN'},
                        {field: 'currency', title: __('Currency'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), sortable: true, operate: false, formatter: Table.api.formatter.status, searchList: {1: __('Paid'), 0: __('Unpaid')}},
                        {field: 'createtime', title: __('Createtime'), sortable: true, operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'paytime', title: __('Paytime'), sortable: true, operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime}
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
