/* Is that needed?
$(function() {
    $('a.arrow').each(function() {
        if ($(this).attr('href') == requestPath) {
            $(this).removeAttr('href', '');
            $(this).css('text-decoration', 'none');
            $(this).html($(this).text() + ' &darr;');
        } else {
            $(this).text($(this).text().replace(/&darr;/, ''));
        }
    });
});
*/

function Highlighter() {
    var self = this;
    self.lines = [];

    self.parseInt = function(data) {
        return parseInt(data.match(/\d+/)[0]);
    }

    self.setup = function() {
        $('.linenos span').click(self.selectHandler);
        self.initFromHash();
    }

    self.unselectLine = function(number) {
        $('#ln' + number).removeClass('selected');
        $('#lc' + number).removeClass('selected');
    }

    self.selectLine = function(number) {
        $('#ln' + number).addClass('selected');
        $('#lc' + number).addClass('selected');
    }

    self.selectHandler = function(event) {
        var elem = $(this);
        var number = self.parseInt(elem.attr('id'));

        if (!event.ctrlKey) {
            $.each(self.lines, function(idx, item) {
                self.unselectLine(item);
            });
            self.lines = [];
        }

        if (-1 == $.inArray(number, self.lines)) {
            self.lines[self.lines.length] = number;
        } else {
            self.lines = $.grep(self.lines, function(item, idx) {
                return item != number;
            });
            self.unselectLine(number);
        }

        self.lines.sort(function(a, b) {
            return a - b;
        });

        $.each(self.lines, function(idx, item) {
            self.selectLine(item);
        });

        self.updateHash();
    }

    self.updateHash = function() {
        location.hash = 'ln=' + self.lines.join(',');
    }

    self.initFromHash = function() {
        matches = location.hash.match(/ln=([\d,]+)/);
        if (matches) {
            numbers = matches[1].split(/,/);
            var min = null;
            $.each(numbers, function(idx, number) {
                $('#ln' + number).trigger({type: 'click', ctrlKey: true});
                if (min == null || parseInt(number) < min) {
                    min = parseInt(number);
                }
            });
            if (min) {
                $(window).scrollTop($('#lc' + min).offset().top - 100);
            }
        }
    }

    self.setup();
}


$(function() {
    var hl = new Highlighter();
});

function togglev() {
    if (document.getElementsByTagName("ol")[0].style.listStyle.substr(0, 4) == "none") {
        document.getElementsByTagName("ol")[0].style.listStyle = "decimal";
        document.getElementsByTagName("ol")[0].style.paddingLeft = "48px"
    } else {
        document.getElementsByTagName("ol")[0].style.listStyle = "none";
        document.getElementsByTagName("ol")[0].style.paddingLeft = "5px"
    }
}
function getElementsByClassName(a, b) {
    if (a.getElementsByClassName) {
        return a.getElementsByClassName(b)
    } else {
        return function c(a, b) {
            if (b == null) b = document;
            var c = [],
                d = b.getElementsByTagName("*"),
                e = d.length,
                f = new RegExp("(^|\\s)" + a + "(\\s|$)"),
                g, h;
            for (g = 0, h = 0; g < e; g++) {
                if (f.test(d[g].className)) {
                    c[h] = d[g];
                    h++
                }
            }
            return c
        }(b, a)
    }
}
function togglew(a) {
    var b = getElementsByClassName(document, a),
        c = b.length;
    for (var d = 0; d < c; d++) {
        var e = b[d];
        if (e.style.whiteSpace == "nowrap") {
            e.style.whiteSpace = "normal"
        } else {
            e.style.whiteSpace = "nowrap"
        }
    }
}