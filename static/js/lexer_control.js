function chooseLexer(link) {
    var lexer = parseLexer($(link).attr('class'));
    $('.lexers a').removeClass('selected');

    /* We have to use [class="foo"] insted .foo notation
     * because the last notation will fail if the class name
     * contains dot symbol */
    class_selector = '[class="lexer-' + lexer + '"]';

    if (!$('.lexers ' + class_selector).length) {
        var item = $('.lexer-template').clone();
        item.removeClass('lexer-template').addClass('lexer-' + lexer);
        $('.more-box').append(item);
        item.text($(link).text());
        item.show();
    }
    $(class_selector).addClass('selected');
    $('#id_lexer').val(lexer);
    $('.more-lexers').hide();
}

function toggleMoreList() {
    $('.more-lexers').toggle();
    if ($('.more-lexers').css('display') == 'block') {
        setTimeout(function() {window.moreListVisible = true;});
    } else {
        window.moreListVisible = false;
    }
}

function parseLexer(data) {
    return data.match(/lexer-[-_a-z0-9.]+/).toString().replace(/lexer-/, '');
}

$(document).keyup(function(event) {
    if (event.keyCode == 27) {
        $('.more-lexers').hide();
    }
});

$('*').click(function() {
    var lexers = $('.more-lexers');
    if (window.moreListVisible) {
        lexers.hide();
    }
    window.moreListVisible = false;
});
