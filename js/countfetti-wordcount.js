
jQuery(function ($) {
    var countfetti_counter = 0;
    var countfetti_shower = $("#countfetti-word-shower").val();
    var countfetti_triggered = 0;
    var countfetti_target_continous = false;

    function trigger_countfetti_shower() {
        if (countfetti_shower != 1)
            return;
        $('[class*=confetti]').remove();
        var randPosX = $("#wp-content-wrap").position().left + Math.floor(Math.random() * $("#wp-content-editor-tools").width());
        var randPosY = $("#wp-content-wrap").position().top + Math.floor(Math.random() * $("#wp-content-editor-tools").height());

        $('#wp-content-wrap').confetti({
            x: randPosX,
            y: randPosY,
            complete: function () {
                countfetti_counter--;
                if (countfetti_counter > 0) {
                    trigger_countfetti_shower();
                }
            }
        });

    }

    var countfetti_target = parseInt($("#countfetti-target").val());
    $('.word-count').on('DOMSubtreeModified', function () {
        var count = parseInt($('#wp-word-count .word-count').text());
        render_countfetti_meter(count, countfetti_target);
    });

    $(window).on('resize', function () {
        var count = parseInt($('#wp-word-count .word-count').text());
        render_countfetti_meter(count, countfetti_target);
    });

    var html = '<div style="margin:10px 0 0"><canvas id="countfetti-progress-meter" title="Click to change view"></canvas></div>';
    $('#post-body-content').append(html);
    $('#post-body-content').append('<div id="countfetti-word-projected" style="text-align:center"></div>');

    render_countfetti_meter(0, countfetti_target);


    function render_countfetti_meter(wordcount, countfetti_target) {
        if (isNaN(wordcount)) {
            return;
        }
        if (countfetti_triggered === 0) {
            countfetti_triggered = (wordcount >= countfetti_target);
        } else if (countfetti_triggered === false) {
            if (wordcount >= countfetti_target) {
                countfetti_triggered = true;
                countfetti_counter = 6;
                trigger_countfetti_shower();
            }
        } else if (countfetti_triggered === true) {
            if (wordcount < countfetti_target) {
                // reset back - words drop below countfetti_target
                countfetti_triggered = false;
            }
        }

        var canvas = document.getElementById("countfetti-progress-meter");
        canvas.width = $("#post-body-content").width();
        canvas.height = 16;
        var ctx = canvas.getContext("2d");
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (wordcount >= countfetti_target)
        {
            ctx.font = "13px Arial";
            var gradient = ctx.createLinearGradient(0, 0, canvas.width, 0);
            gradient.addColorStop(0, 'red');
            gradient.addColorStop(1 / 6, 'orange');
            gradient.addColorStop(2 / 6, 'yellow');
            gradient.addColorStop(3 / 6, 'green');
            gradient.addColorStop(4 / 6, 'blue');
            gradient.addColorStop(5 / 6, 'Indigo');
            gradient.addColorStop(1, 'Violet');
            ctx.globalAlpha = 0.5;
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, 16);
            ctx.textAlign = "center";
            ctx.fillStyle = "#ffffff";
            ctx.globalAlpha = 1;
            ctx.fillText(wordcount.toString(), canvas.width / 2, 12);
            return;
        }

        if (countfetti_target_continous) {
            countfetti_target = (parseInt(wordcount / 100) + 1) * 100;
        }

        ctx.fillStyle = "#a3d2e2";
        ctx.strokeStyle = "#777777";
        var fillWidth = parseInt((wordcount / countfetti_target) * canvas.width);
        fillWidth = (fillWidth > canvas.width) ? canvas.width : fillWidth;
        ctx.fillRect(0, 0, fillWidth, 16);
        var spacing = (canvas.width - 1) / countfetti_target;
        var loop = 0;
        var marker = countfetti_target <= 1000 ? 20 : (countfetti_target <= 10000 ? 100 : 1000);
        var market_cycle = countfetti_target <= 1000 ? 5 : (countfetti_target <= 10000 ? 10 : 100);
        for (var i = 0; i < canvas.width; i += marker * spacing) {
            var y = (loop % market_cycle == 0) ? 0 : 6;
            var x = parseInt(i);
            var offset = (loop % market_cycle == 0) ? 0 : 0.5;
            offset = (i == 0) ? 1 : offset;

            ctx.moveTo(x + offset, y);
            ctx.lineTo(x + offset, 16);
            loop++;
        }
        ctx.moveTo(0, 16);
        ctx.lineTo(canvas.width, 16);
        ctx.stroke();
        ctx.font = "12px bold 'Fauna One'";
        ctx.textAlign = "right";
        ctx.fillStyle = "#333";
        if (fillWidth < canvas.width - 30) {
            ctx.fillText(countfetti_target.toString(), canvas.width - 4, 12);
        }
        ctx.textAlign = "right";
        ctx.fillStyle = "#000000";
        ctx.fillText(wordcount.toString(), fillWidth - 4, 12);

        if ($("#post-status-display").text() == 'Published') {
        } else {
            var wordcount_projected = parseInt($("#countfetti-word-total").val()) + parseInt(wordcount);
            $("#countfetti-word-projected").text($("#countfetti-word-total").val().toLocaleString() + ' + ' + wordcount.toString() + ' = ' + wordcount_projected.toLocaleString() + ' words');
        }
    }


    $("#countfetti-progress-meter").click(function () {
        var count = parseInt($('#wp-word-count .word-count').text());
        countfetti_target_continous = !countfetti_target_continous;
        render_countfetti_meter(count, countfetti_target);
        if (count >= countfetti_target) {
            countfetti_counter = 6;
            trigger_countfetti_shower();
        }
    });

    if ($('input[value=countfetti_post_target]').length > 0) {
        var meta_id = '#' + $('input[value=countfetti_post_target]').attr('id');
        meta_id = meta_id.replace("key", "value");
        $(document).ajaxSuccess(function () {
            var count = parseInt($('#wp-word-count .word-count').text());
            var target_new = parseInt($(meta_id).text().trim());
            if (!isNaN(target_new) && countfetti_target != target_new && target_new >= 100 && target_new <= 50000) {
                countfetti_target_continous = false;
                countfetti_target = target_new;
                render_countfetti_meter(count, countfetti_target);                
            }
        });
    }


});
