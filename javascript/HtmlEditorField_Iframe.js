/**
 * File: HtmlEditorField_Iframe.js
 */
var ss = ss || {};

(function($) {
    $.entwine('ss', function($) {
        /**
         * See framework/javascript/HtmlEditorField.js
         */
        $('form.htmleditorfield-mediaform').entwine({
            updateFromEditor: function() {
                var self = this, node = this.getSelection();

                // Intercept this class first, as the tinymce node for an iframe is a transparent image
                if (node.hasClass('mceItemIframe')) {
                    // Use of tinyMCE's JSON parse is intentional (honestly)
                    var data = tinymce.util.JSON.parse(node.attr('data-mce-json')),
                        params = data.params;

                    this.showIframeView(params['src'], params['data-width'], params['data-height'])
                        .done(function(iframeField) {
                            iframeField.updateFromNode(node);
                            self.toggleCloseButton();
                            self.redraw();
                        });

                    this.redraw();
                } else {
                    this._super();
                }
            },
            showIframeView: function(idOrUrl, width, height) {
                var self = this, params = {IframeURL: idOrUrl, Width: width, Height: height},
                    item = $('<div class="ss-htmleditorfield-file loading" />');

                this.find('.content-edit').append(item);

                var dfr = $.Deferred();

                $.ajax({
                    url: $.path.addSearchParams(this.attr('action').replace(/MediaForm/, 'viewiframe'), params),
                    success: function(html, status, xhr) {
                        var newItem = $(html).filter('.ss-htmleditorfield-file');
                        item.replaceWith(newItem);
                        self.redraw();
                        dfr.resolve(newItem);
                    },
                    error: function() {
                        item.remove();
                        dfr.reject();
                    }
                });

                return dfr.promise();
            }
        });

        /**
         * Insert an iframe tag into the content
         */
        $('form.htmleditorfield-mediaform .ss-htmleditorfield-file.iframe').entwine({
            getAttributes: function() {
                var width = this.find(':input[name=Width]').val(),
                    height = this.find(':input[name=Height]').val();

                return {
                    'src' : this.find('#ClickableURL .file').attr('href'),
                    'width' : width ? parseInt(width, 10) : null,
                    'height' : height ? parseInt(height, 10) : null,
                    'class' : this.find('select[name=CSSClass]').val()
                };
            },
            getExtraData: function() {
                var width = this.find(':input[name=Width]').val(),
                    height = this.find(':input[name=Height]').val();

                return {
                    'CaptionText': this.find(':input[name=CaptionText]').val(),
                    'Url': this.find(':input[name=URL]').val(),
                    'width' : width ? parseInt(width, 10) : null,
                    'height' : height ? parseInt(height, 10) : null,
                    'cssclass': this.find('select[name=CSSClass]').val()
                };
            },
            insertHTML: function(ed) {
                var form = this.closest('form'),
                    node = form.getSelection();

                if (!ed) ed = form.getEditor();

                // Get the attributes & extra data
                var attrs = this.getAttributes(),
                    extraData = this.getExtraData();

                // Find the element we are replacing - either the image placeholder for the iframe, its wrapper parent, or nothing (if creating)
                var replacee = (node && node.is('img')) ? node : null;
                if (replacee && replacee.parent().is('.captionImage')) replacee = replacee.parent();

                // Create the "iframe" - we have to use an image placeholder, as rendering iframes inside TinyMCE = bad
                var iframe = tinyMCE.activeEditor.plugins.media.dataToImg({
                    'type': 'iframe',
                    'width': attrs.width,
                    'height': attrs.height,
                    'params': {'src': attrs.src},
                    'video': {'sources': []}
                });

                // Any existing figure or caption node
                var container = node.parent('.captionImage'),
                    caption = container.find('.caption');

                // If we've got caption text, we need a wrapping div.captionImage and sibling p.caption
                if (extraData.CaptionText) {
                    if (!container.length) {
                        container = $('<div></div>');
                    }

                    container.attr('class', 'captionImage '+attrs['class']).css('width', attrs.width);

                    if (!caption.length) {
                        caption = $('<p class="caption"></p>').appendTo(container);
                    }

                    caption.attr('class', 'caption '+attrs['class']).text(extraData.CaptionText);
                }
                // Otherwise forget they exist
                else {
                    container = caption = null;
                }

                // The element we are replacing the replacee with
                var replacer = container ? container : iframe;

                // If we're replacing something, and it's not with itself, do so
                if (replacee && replacee.not(replacer).length) {
                    replacee.replaceWith(replacer);
                }

                // If we have a wrapper element, make sure the iframe is the first child - iframe might be the
                // replacee, and the wrapper the replacer, and we can't do this till after the replace has happened
                if (container) {
                    node.remove(); // Remove the original node, we don't need it now
                    container.prepend(iframe);
                }

                // If we don't have a replacee, then we need to insert the whole HTML
                if (!replacee) {
                    // Otherwise insert the whole HTML content
                    ed.repaint();
                    ed.insertContent($('<div />').append(replacer).html(), {skip_undo : 1});
                }

                ed.addUndo();
                ed.repaint();
            },
            updateFromNode: function(node) {
                this.find(':input[name=Width]').val(node.width());
                this.find(':input[name=Height]').val(node.height());
                this.find('select[name=CSSClass]').val(node.data('cssclass'));
                this.find(':input[name=CaptionText]').val(node.siblings('.caption:first').text());
            }
        });

        $('form.htmleditorfield-form.htmleditorfield-mediaform input.iframeurl').entwine({
            onkeyup: function() {
                this.validate();
            },
            onchange: function() {
                this.validate();
            },
            getAddButton: function() {
                return this.closest('.CompositeField').find('button.add-iframe');
            },
            validate: function() {
                var val = this.val(),
                    orig = val;

                // Ensure we always have a prefix... http is better than nothing
                val = (val.indexOf('://') === -1) ? 'http://' + val : val;
                if (orig !== val) this.val(val);

                this.getAddButton().button(!!val ? 'enable' : 'disable');
                return !!val;
            }
        });

        /**
         * Show the second step after adding a URL
         */
        $('form.htmleditorfield-form.htmleditorfield-mediaform .add-iframe').entwine({
            getURLField: function() {
                return this.closest('.CompositeField').find('input.iframeurl');
            },
            onclick: function(e) {
                var urlField = this.getURLField(), container = this.closest('.CompositeField'), form = this.closest('form');

                if (urlField.validate()) {
                    container.addClass('loading');
                    form.showIframeView(urlField.val(), '', '').done(function() {
                        container.removeClass('loading');
                    });
                    form.redraw();
                }

                return false;
            }
        });

        /**
         * Prevent dimensions from automatically updating to match each other
         */
        $('form.htmleditorfield-mediaform .ss-htmleditorfield-file.iframe .dimensions :input').entwine({
            onfocusout: function(e) {
                return false;
            }
        });
    });
})(jQuery);
