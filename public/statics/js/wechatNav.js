$(function(){
  var fnums = $(".pre_menu_item").length;
  if(fnums>=0){
    var i = fnums + 3;
  }else{
    var i = 1;
  }
    
   //var num = $('#menuBox').length;获取总数
      $('.js_addMenuBox').click(function(){
        var fnum = $(".pre_menu_item").length;

        $(this).before('<li class="pre_menu_item current fanvmenu globel0'+i+'" id="menu_0'+i+'" data-id="0'+i+'"><a href="javascript:void(0)" class="pre_menu_link" data-id="0'+i+'">菜单名称</a>                            <div class="sub_pre_menu_box" id="menu0'+i+'"  >                                <ul class="sub_pre_menu_list" id="add_0'+i+'"  >                                    <p class="js_addMenuBox01 " id="sonNav0'+i+'" data-id="0'+i+'">                                        <a href="javascript:void(0);" class="jsSubView js_addL2Btn" title="最多添加5个子菜单" draggable="false" >                                            <span class="sub_pre_menu_inner js_sub_pre_menu_inner"><i class="icon14_menu_add"></i></span>                                        </a>                                    </p>                                </ul>                                <i class="arrow arrow_out"></i>                                <i class="arrow arrow_in"></i>                            </div>                        </li>');

        $('.js_addMenuBox').removeClass('size1of1');
        $('.js_addMenuBox').addClass('size1of2');
        $('.js_addMenuBox').html('<a href="javascript:void(0);" class="pre_menu_link js_addL1Btn" title="最多添加3个一级菜单" draggable="false">                            <i class="icon14_menu_add"></i>');
        $('#default').hide();
        $('#mainContent').append('<div  class="portable_editor to_left sonmenu0'+i+'" id="fmenu0'+i+'"><div class="editor_inner"><div class="global_mod float_layout menu_form_hd js_second_title_bar"><h4 class="global_info">菜单名称</h4><div class="global_extra"><a href="javascript:void(0);" data-id="0'+i+'">删除菜单</a></div><div class="clear"></div></div></div><div class="menu_form_bd" id="view"><div id="js_innerNone" style="display: none;" class="msg_sender_tips tips_global">已添加子菜单，仅可设置菜单名称。</div><div class="frm_control_group js_setNameBox"><label for="" class="frm_label"><strong class="title js_menuTitle">菜单名称</strong></label><div class="frm_controls"> <span class="frm_input_box with_counter counter_in append input0'+i+'" ><input type="text" class="frm_input js_menu_name form-control" name="data0'+i+'[name][0]" value="菜单名称" required="required" data-id= "0'+i+'" ></span><p class="frm_msg fail js_titleEorTips dn" style="display: none;">字数超过上限</p><p class="frm_msg fail js_titlenoTips dn" style="display: none;">请输入菜单名称</p><p class="frm_tips js_titleNolTips">字数不超过4个汉字或8个字母</p></div></div></div> <div class="frm_control_group yjmenu0'+i+'" ><label for="" class="frm_label"><strong class="title js_menuContent">菜单内容</strong></label> <div class="frm_controls frm_vertical_pt"><input type="hidden" name="data0'+i+'[type][0]" class="frm_radio fradio0'+i+'" value="one" id="menuone0'+i+'" data-id="0'+i+'"><label class="frm_radio_label js_radio_sendMsg selected f1info" data-editing="0" > <i class="icon_radio"></i>  <input type="radio" name="data0'+i+'[type][0]" class="frm_radio fradio0'+i+'" value="txt" data-id="0'+i+'" id="fkey0'+i+'"><span class="lbl_content">发送消息</span></label><label class="frm_radio_label js_radio_url f11info" data-editing="0"><i class="icon_radio"></i><input type="radio" name="data0'+i+'[type][0]" class="frm_radio fradio0'+i+'" id="furl0'+i+'" value="url" checked="checked"  data-id="0'+i+'"><span class="lbl_content">跳转网页</span></label><label class="frm_radio_label js_radio_url f11info" data-editing="0"> <i class="icon_radio"></i> <input type="radio" name="data0'+i+'[type][0]" class="frm_radio fradio0'+i+'" id="fxcx0'+i+'" value="miniprogram"  data-id="0'+i+'"> <span class="lbl_content">跳转小程序</span></label></div></div><div class="menu_content_container yjmenu0'+i+'"><div class="menu_content url jsMain" id="txt0'+i+'" style="display: none"> <p class="menu_content_tips tips_global">订阅者点击该子菜单会显示文本消息</p><textarea name="data0'+i+'[key][0]'+i+'" rows="5" cols="50" class="form-control fmvalue0'+i+'" style="margin-top: 10px;" id="key0'+i+'"></textarea></div><div class="menu_content url jsMain" id="url0'+i+'" style="display: block;"><p class="menu_content_tips tips_global">订阅者点击该子菜单会跳到以下链接</p><div class="frm_control_group" style=" margin-top:10px;"><label for="" class="frm_label">页面地址</label><div class="frm_controls"><span class="frm_input_box disabled"><input type="url" class="frm_input fmvalue0'+i+'" id="urlText0'+i+'" name="data0'+i+'[url][0]" ></span><p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 请勿添加其他公众号的主页链接</p></div></div></div> <div class="menu_content url jsMain" id="miniprogram0'+i+'" style="display: none;" >  <p class="menu_content_tips tips_global">前提条件是你的公众号已绑定了小程序,订阅者点击该子菜单会跳到以下小程序</p> <div class="frm_control_group" style=" margin-top:10px;"><label for="" class="frm_label">小程序路径</label><div class="frm_controls"><span class="frm_input_box disabled"><input type="text" class="frm_input fmvalue0'+i+'"  name="data0'+i+'[path][0]'+i+'" id="path0'+i+'"></span><p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 已选择小程序-本地部署</p></div></div><div class="frm_control_group" style=" margin-top:10px;"><label for="" class="frm_label">备用网页</label><div class="frm_controls">      <span class="frm_input_box disabled">  <input type="url" class="frm_input fmvalue0'+i+'"  name="data0'+i+'[burl][0]'+i+'" id="burl0'+i+'" >     </span>       <p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 旧版本微信客户端无法支持小程序,用户点击菜单时将会打开备用网页</p>  </div>       </div>   </div>   </div>    </div>');
            
           /* $('.portable_editor').hide();
            $('#fmenu_0'+i).show();*/
            $('.pre_menu_item').addClass('size1of2');
            $('.pre_menu_item').removeClass('current');
            $('#menu_0'+i).addClass('current');
			      var m1 = $('#caidan01').val();
            var m2 = $('#caidan02').val();
            var m3 = $('#caidan03').val();
            if(fnum<0){
                $('#addmenu').show();
            }
      			if(fnum==1){
      				$('.sub_pre_menu_box').hide();
      				$('#menu0'+i).show();
              //$('#caidan01').val('0'+i);
              
              if(m1!=''){
                if(m2==''){
                  $('#caidan02').val('0'+i);
                  $('#caidan02').removeClass();
                  $('#caidan02').addClass('caidan0'+i);
                }else{
                  $('#caidan03').val('0'+i);
                  $('#caidan03').removeClass();
                  $('#caidan03').addClass('caidan0'+i);
                }
              }else{
                $('#caidan01').val('0'+i);
                $('#caidan01').removeClass();
                $('#caidan01').addClass('caidan0'+i);
              }
              
      			}
            if(fnum==2){
                $('.js_addMenuBox').removeClass('size1of2'); 
                $('.pre_menu_item').addClass('size1of3');
				        $('.sub_pre_menu_box').hide();
				        $('#menu0'+i).show();
                if(m2!=''){
                if(m1==''){
                  $('#caidan01').val('0'+i);
                  $('#caidan01').removeClass();
                  $('#caidan01').addClass('caidan0'+i);
                }else{
                  $('#caidan03').val('0'+i);
                  $('#caidan03').removeClass();
                  $('#caidan03').addClass('caidan0'+i);
                }
                }else{
                  $('#caidan02').val('0'+i);
                  $('#caidan02').removeClass();
                  $('#caidan02').addClass('caidan0'+i);
                }
                
            }else if(fnum==3){
                $('.js_addMenuBox').hide();
                $('.pre_menu_item').removeClass('size1of2');
                $('.pre_menu_item').addClass('size1of3');
				        $('.sub_pre_menu_box').hide();
				        $('#menu0'+i).show();
                if(m3!=''){
                if(m1==''){
                  $('#caidan01').val('0'+i);
                  $('#caidan01').removeClass();
                  $('#caidan01').addClass('caidan0'+i);
                }else{
                  $('#caidan02').val('0'+i);
                  $('#caidan02').removeClass();
                  $('#caidan02').addClass('caidan0'+i);
                }
                }else{
                  $('#caidan03').val('0'+i);
                  $('#caidan03').removeClass();
                  $('#caidan03').addClass('caidan0'+i);
                }
                
                
            }   
            i++;
      });  
      $(document).on('click','.pre_menu_link',function(e){
          var fid = $(this).data("id");
          $('.sub_pre_menu_box').hide();
          $('.pre_menu_item').removeClass('current');
          $('.sub_pre_menu_list li').removeClass('current');
          $("#menu_"+fid).addClass('current');
          $("#menu_"+fid).children('.sub_pre_menu_box').show();
          $('.portable_editor').hide();
          $('#fmenu'+fid).show();
      });
      var d = 1;
       $(document).on('click','.pre_menu_item .js_addMenuBox01',function(e){
            var sid  = $(this).data("id");
            var sondiv = '#add_'+sid+' li';
            //alert(sondiv);
            var sunnum = $(sondiv).length;
            if(sunnum>=0){
                var d = sunnum + 3;
            }
            //alert(id);
            $('#add_'+sid).prepend('<li id="menu'+sid+'_'+d+'" class="jslevel2 selected glmenu globel'+sid+'" data-id="'+sid+'_'+d+'"><span class="sub_pre_menu_inner js_sub_pre_menu_inner"><i class="icon20_common sort_gray"></i><span class="js_l2Title">子菜单名称</span></li>');
            $('#mainContent').prepend('<div  class="portable_editor to_left sonmenu'+sid+'" id="fmenu'+sid+'_'+d+'">                        <div class="editor_inner">                            <div class="global_mod float_layout menu_form_hd js_second_title_bar">                                     <h4 class="global_info">子菜单名称</h4>                                                            <div class="global_extra">                                                                 <a href="javascript:void(0);" data-id="'+sid+'_'+d+'">删除菜单</a>                                                            </div>                                 <div class="clear"></div>                                                   </div>                        </div>                        <div class="menu_form_bd" id="view">                            <div id="js_innerNone" style="display: none;" class="msg_sender_tips tips_global">已添加子菜单，仅可设置菜单名称。</div>                            <div class="frm_control_group js_setNameBox">                                <label for="" class="frm_label"><strong class="title js_menuTitle">子菜单名称</strong></label>                                <div class="frm_controls">                                                                        <span class="frm_input_box with_counter counter_in append input'+sid+'_'+d+'">                                        <input type="text" class="frm_input js_menu_name" name="data'+sid+'[name]['+d+']" value="子菜单名称"  required="required" data-id= "'+sid+'_'+d+'" >                                     </span>                                                                        <p class="frm_msg fail js_titleEorTips dn" style="display: none;">字数超过上限</p>                                    <p class="frm_msg fail js_titlenoTips dn" style="display: none;">请输入菜单名称</p>                                    <p class="frm_tips js_titleNolTips">字数不超过5个汉字或8个字母</p></div>                                                            </div>                        </div> <div class="frm_control_group">                            <label for="" class="frm_label"><strong class="title js_menuContent">菜单内容</strong></label>                            <div class="frm_controls frm_vertical_pt">                                <label class="frm_radio_label js_radio_sendMsg selected f1info" data-editing="0" >                                    <i class="icon_radio"></i>                                    <input type="radio" name="data'+sid+'[type]['+d+']" class="frm_radio fradio'+sid+'_'+d+'" value="txt" data-id="'+sid+'_'+d+'">                                    <span class="lbl_content">发送消息</span>                                                                    </label>                                <label class="frm_radio_label js_radio_url f11info" data-editing="0">                                    <i class="icon_radio"></i>                                    <input type="radio" name="data'+sid+'[type]['+d+']" class="frm_radio fradio'+sid+'_'+d+'" value="url" checked="checked"  data-id="'+sid+'_'+d+'">                                    <span class="lbl_content">跳转网页</span>                                </label>     <label class="frm_radio_label js_radio_url f11info" data-editing="0"> <i class="icon_radio"></i> <input type="radio" name="data'+sid+'[type]['+d+']" class="frm_radio fradio'+sid+'_'+d+'" value="miniprogram" data-id="'+sid+'_'+d+'"> <span class="lbl_content">跳转小程序</span>                                </label>                             </div>                        </div>                       <div class="menu_content_container">                            <div class="menu_content url jsMain" id="txt'+sid+'_'+d+'" style="display: none"> <p class="menu_content_tips tips_global">订阅者点击该子菜单会显示文本消息</p>                       <textarea name="data'+sid+'[key]['+d+']" class="form-control fmvalue'+sid+'_'+d+'" rows="5" cols="50" style="margin-top: 10px;" id="key'+sid+'_'+d+'"></textarea>                            </div>                            <div class="menu_content url jsMain" id="url'+sid+'_'+d+'" style="display: block;">                                <p class="menu_content_tips tips_global">订阅者点击该子菜单会跳到以下链接</p>                                <div class="frm_control_group" style=" margin-top:10px;">                                    <label for="" class="frm_label">页面地址</label>                                    <div class="frm_controls">                                        <span class="frm_input_box disabled">                                                                                               <input type="url" class="frm_input fmvalue'+sid+'_'+d+'"  required="required" name="data'+sid+'[url]['+d+']" id="urlText'+sid+'_'+d+'">                                        </span>                                         <p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 请勿添加其他公众号的主页链接</p>                                    </div>                                </div>                            </div>      <div class="menu_content url jsMain" id="miniprogram'+sid+'_'+d+'" style="display: none;">                                <p class="menu_content_tips tips_global">前提条件是你的公众号已绑定了小程序,订阅者点击该子菜单会跳到以下小程序</p>                                <div class="frm_control_group" style=" margin-top:10px;">                                    <label for="" class="frm_label">小程序路径</label>                                    <div class="frm_controls">                                        <span class="frm_input_box disabled">                                                                                               <input type="text" class="frm_input fmvalue'+sid+'_'+d+'"  name="data'+sid+'[path]['+d+']" id="path'+sid+'_'+d+'" >                                        </span>                                         <p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 已选择小程序-本地部署</p>                                    </div>                                </div>                                <div class="frm_control_group" style=" margin-top:10px;">                                    <label for="" class="frm_label">备用网页</label>                                    <div class="frm_controls">      <span class="frm_input_box disabled">  <input type="url" class="frm_input fmvalue'+sid+'_'+d+'"  name="data'+sid+'[burl]['+d+']" id="burl'+sid+'_'+d+'" >     </span>       <p class="profile_link_msg_global menu_url mini_tips warn dn js_warn" > 旧版本微信客户端无法支持小程序,用户点击菜单时将会打开备用网页</p>  </div>       </div>   </div>                  </div>                    </div>');
            $('.portable_editor').hide();
            $('#fmenu'+sid+'_'+d).show();
            $('.yjmenu'+sid).hide();
            $('.fradio'+sid).removeAttr('checked','true');
            $('#menuone'+sid).attr('checked','true');
            $('.fmvalue'+sid).val('');
            if(sunnum>=4){
                $('#add_'+sid+' .js_addMenuBox01').hide();
                
                
            }else if(sunnum<0){
                $('#add_'+sid+' .js_addMenuBox01').show();
                $('.yjmenu'+sid).show();
                $('.fradio'+sid).removeAttr('checked','true');
                $('#furl'+sid).attr('checked','true');
            }
            d++;
       });
        
        $(document).on('click','.sub_pre_menu_list li',function(e){
            var zid = $(this).data("id");
            if(zid!=''){
                //alert(zid);
                $('.pre_menu_item').removeClass('current');
                $('.sub_pre_menu_list li').removeClass('current');
                $(this).addClass('current');
                $('.portable_editor').hide();
                $('#fmenu'+zid).show();
            }
        });
        
        //点击删除
        $(document).on('click','.global_extra a',function(){
            var delid = $(this).data("id");
            var to = delid.substr(0,2); 
            var sondiv = '#add_'+to+' li';
            if(delid == to){
              //alert(delid == to)
              $('.sonmenu'+to).remove();
              $('.globel'+to).remove();
              $('.caidan'+to).val('');
            }else{

              $('#menu'+delid).remove();
              $('#fmenu'+delid).remove();
              $('#default').show();
            }
            var sunnum = $(sondiv).length;
            //alert(sunnum);
            var fsum = $(".pre_menu_item").length; 
            if(fsum==3){
                $('.js_addMenuBox').html('<a href="javascript:void(0);" class="pre_menu_link js_addL1Btn" title="最多添加3个一级菜单" draggable="false">                            <i class="icon14_menu_add"></i>');
                $('.js_addMenuBox').removeClass('size1of1');
                $('.js_addMenuBox').removeClass('size1of2');
                $('.js_addMenuBox').addClass('size1of3');
                $('.js_addMenuBox').show();
            }else if(fsum==2){ 
                $('.js_addMenuBox').html('<a href="javascript:void(0);" class="pre_menu_link js_addL1Btn" title="最多添加3个一级菜单" draggable="false">                            <i class="icon14_menu_add"></i>');
                $('.js_addMenuBox').removeClass('size1of1');
                $('.pre_menu_item').removeClass('size1of3 size1of1');
                $('.pre_menu_item').addClass('size1of2');
                $('.js_addMenuBox').addClass('size1of2');
                $('.js_addMenuBox').show();
            }else if(fsum==1){
                $('.js_addMenuBox').html('<a href="javascript:void(0);" class="js_addL1Btn js_addL1Btn1"  title="最多添加3个一级菜单">+ 添加菜单</a>');
                $('.js_addMenuBox').removeClass('size1of3 size1of2');
                $('.js_addMenuBox').addClass('size1of1');
            }else{
                 $('.js_addMenuBox').html('<a href="javascript:void(0);" class="js_addL1Btn js_addL1Btn1"  title="最多添加3个一级菜单">+ 添加菜单</a>');
                $('.js_addMenuBox').removeClass('size1of3 size1of2');
                $('.js_addMenuBox').addClass('size1of1');
            }
            var add = "#add_"+to+" .js_addMenuBox01";
            //alert(add);
            if(sunnum<=4){
               $(add).show();
            }
            //alert(sunnum);
            if(sunnum<=0){
              //alert(sunnum);
              $('.yjmenu'+to).show();
            }
        });

        //关联菜单名称
        $(document).on('change','.js_menu_name',function(){
            var cid = $(this).data('id');
            var str =  cid.length;
            var cname = $(this).val();
            if(!cname.match(/^[A-Za-z]{1,8}|[\u4E00-\u9FA5]{1,5}$/)){
              $(this).focus();
              $('.input'+cid).css('border','1px solid #ff0000');
              return false;
            }else{
                if(str==2){
                $('#menu_'+cid+' a.pre_menu_link').html(cname);
              }else{
                //var chdiv = ""
                $('#menu'+cid+' .js_l2Title').html(cname);
              }
            }

        });
        //单选按钮
        $(document).on('click','.frm_radio',function(){
          var divshow = $(this).val();
          var divid = $(this).data('id');
          if(divshow=='txt'){
              $('#txt'+divid).show();
              $('#miniprogram'+divid).hide();
              $('#url'+divid).hide();
              //$('input[type="text"]').attr("required", "true");
              //var txt = "textarea[name='txt+divid+']"
              //$('textarea[name="txt"]').attr("required","true");
              $("#key"+divid).attr("required", "true");
              $("#urlText"+divid).removeAttr("required", "true");
              $("#path"+divid).removeAttr("required", "true");


          }else if(divshow=='url'){
              $('#txt'+divid).hide();
              $('#miniprogram'+divid).hide();
              $('#url'+divid).show();
              $("#urlText"+divid).attr("required", "true");
              $("#key"+divid).removeAttr("required", "true");
              $("#path"+divid).removeAttr("required", "true");

          }else if(divshow=='miniprogram'){
              $('#txt'+divid).hide();
              $('#miniprogram'+divid).show();
              $('#url'+divid).hide();
              $("#path"+divid).attr("required", "true");
              $("#urlText"+divid).removeAttr("required", "true");
              $("#key"+divid).removeAttr("required", "true");
          }
          //alert(divshow);
          //$('.menu_content').hide();
          //$(divshow).show();
        })

      $(document).on('click','#submit',function(){
          //alert(123);
		  $('#myForm').submit();
       });
		
		$('.deleteALL').click(function(){
            swal({
                title: "您确定要删除全部菜单吗",
                text: "删除后要重新添加，请谨慎操作！",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "是的，我要删除！",
                cancelButtonText: "让我再考虑一下…",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function (isConfirm) {
                if (isConfirm) {
                    var id = 'all';
                    $.ajax({
                        type : 'POST',//delete
                        url : '/mlshare/Openplatform/menu?id='+id,
                        dataType : 'json',
                        data : {id:id},
                        async:false,
                        success : function(result) {
                            //layer.close(layer1);
                            if(0 == result.code) {
                                
                                swal("删除成功！", "您已经删除了所有自定义菜单信息。", "success");
                            }  else  {
                                //删除失败
                                swal("删除失败", "删除失败！请稍后重试", "error");
                                
                            }
                        },
                        error : function(){
                            res = false;
                            swal("删除失败", "服务器错误！请稍后重试", "error");
                           
                        }
                    })
                    
                } else {
                    swal("已取消", "您取消了删除操作！", "error");
                }
            });
       }); 
});