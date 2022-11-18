define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/score_log/index' + location.search,
                    // add_url: 'auction/order/add',
                    //edit_url: 'auction/order/edit',
                    // del_url: 'auction/order/del',
                    // multi_url: 'auction/order/multi',
                    // import_url: 'auction/order/import',
                    table: 'user_score_log',
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
                        {field: 'user_id', sortable: true, title: __('User_id')},
                        {field: 'username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'before', title: __('Before'), operate:'BETWEEN'},
                        {field: 'score', title: __('Score'), sortable: true,  operate:'RANGE', addclass: 'red'},
                        {field: 'after', title: __('After'), operate:'BETWEEN'},
                        {field: 'memo', title: __('Memo'), operate:'LIKE'},
                        {field: 'createtime', title: __('Createtime'), sortable: true, operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
