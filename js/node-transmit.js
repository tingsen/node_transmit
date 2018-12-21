(function ($, drupalSettings) {
    var baseUrl = drupalSettings.path.baseUrl;
   $('button.checked-button').on('click', function (e) {
       var $that = $(this);
       var id = $that.data('r');
       if(confirm('您确定要通过审核')){
           $.ajax({
               url: baseUrl + 'check',
               method: 'POST',
               data: {id},
               success: function () {
                   location.reload();
               },
               error: function () {
                   alert('审核失败！')
               }
           })
       }
   });
    $('button.unchecked-button').on('click', function (e) {
        var $that = $(this);
        var id = $that.data('r');
        const msg = prompt('请输入您的驳回理由：');
        if(msg){
            console.log(msg);
            $.ajax({
                url: baseUrl + 'reject',
                method: 'POST',
                data: {id,msg},
                success: function () {
                    location.reload();
                },
                error: function () {
                    alert('审核失败！')
                }
            })
        }
    })
})(jQuery, drupalSettings);