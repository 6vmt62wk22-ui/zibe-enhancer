/**
 * 子比主题美化增强 - 后台管理脚本
 */
jQuery(function ($) {

  /* ─── 选项卡切换 ──────────────────────────────────────── */
  var tabs   = $('.zibe-tab');
  var panels = $('.zibe-panel');

  tabs.on('click', function () {
    var target = $(this).data('tab');
    tabs.removeClass('active');
    panels.removeClass('active');
    $(this).addClass('active');
    $('#tab-' + target).addClass('active');

    // 记录最后活跃 Tab
    try { localStorage.setItem('zibe_enh_tab', target); } catch(e) {}
  });

  // 恢复上次 Tab
  try {
    var savedTab = localStorage.getItem('zibe_enh_tab');
    if (savedTab && $('[data-tab="' + savedTab + '"]').length) {
      $('[data-tab="' + savedTab + '"]').trigger('click');
    }
  } catch(e) {}

  /* ─── 颜色选择器 ──────────────────────────────────────── */
  $('.zibe-color-picker').wpColorPicker({
    change: function (event, ui) {
      // 实时更新预览色块
      $(this).closest('.zibe-row').find('.color-preview')
        .css('background', ui.color.toString());
    }
  });

  /* ─── 重置按钮 ─────────────────────────────────────────── */
  $('.zibe-reset-btn').on('click', function (e) {
    e.preventDefault();
    if (!confirm('确定要将所有设置重置为默认值吗？')) return;

    // 提交一个带 reset 标记的表单
    var form = $(this).closest('form');
    $('<input>').attr({ type: 'hidden', name: 'zibe_reset', value: '1' }).appendTo(form);
    form.submit();
  });

  /* ─── 范围滑块 - 保持 label 同步（防止重复绑定）──────── */
  $(document).on('input', '.zibe-range', function () {
    var $next = $(this).next('.range-val');
    var unit  = $(this).attr('name').indexOf('line_height') !== -1 ? '' : 'px';
    $next.text(this.value + unit);
  });

  /* ─── 自定义字体：下拉联动显示/隐藏 ────────────────── */
  var $fontSelect  = $('#zibe-font-select');
  var $customRows  = $('.zibe-custom-font-row');

  $fontSelect.on('change', function () {
    if ($(this).val() === 'custom') {
      $customRows.slideDown(250);
    } else {
      $customRows.slideUp(200);
    }
  });

  /* ─── 保存时加载指示 ────────────────────────────────────── */
  $('form').on('submit', function () {
    var btn = $(this).find('.zibe-save-btn');
    btn.text('💾 保存中...').prop('disabled', true).css('opacity', '0.8');
  });

  /* ─── 成功提示自动消失 ─────────────────────────────────── */
  var $notice = $('.zibe-notice-success');
  if ($notice.length) {
    setTimeout(function () {
      $notice.fadeOut(600, function () { $(this).remove(); });
    }, 3500);
  }

});
