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
					data = tinymce.util.JSON.parse(node.attr('data-mce-json'));
					this.showIframeView(data.params.src).complete(function() {
						$(this).updateFromNode(node);
						self.toggleCloseButton();
						self.redraw();
					});
				} else if(node.is('img')) {
					this.showFileView(node.data('url') || node.attr('src')).done(function(filefield) {
						filefield.updateFromNode(node);
						self.toggleCloseButton();
						self.redraw();
					});
				}

				this.redraw();
			},
			showIframeView: function(idOrUrl, successCallback) {
				var self = this, params = (Number(idOrUrl) == idOrUrl) ? {ID: idOrUrl} : {FileURL: idOrUrl},
					item = $('<div class="ss-htmleditorfield-file" />');

				item.addClass('loading');
				this.find('.content-edit').append(item);
				return $.ajax({
					url: $.path.addSearchParams(this.attr('action').replace(/MediaForm/, 'viewiframe'), params),
					success: function(html, status, xhr) {
						var newItem = $(html);
						item.replaceWith(newItem);
						self.redraw();
						if(successCallback) successCallback.call(newItem, html, status, xhr);
					},
					error: function() {
						item.remove();
					}
				});
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
					'class' : this.find(':input[name=CSSClass]').val()
				};
			},
			getExtraData: function() {
				var width = this.find(':input[name=Width]').val(),
					height = this.find(':input[name=Height]').val();
				return {
					'CaptionText': this.find(':input[name=CaptionText]').val(),
					'Url': this.find(':input[name=URL]').val(),
					'thumbnail': this.find('.thumbnail-preview').attr('src'),
					'width' : width ? parseInt(width, 10) : null,
					'height' : height ? parseInt(height, 10) : null,
					'cssclass': this.find(':input[name=CSSClass]').val()
				};
			},
			getHTML: function() {
				var el,
					attrs = this.getAttributes(),
					extraData = this.getExtraData(),
					iframeEl = $('<iframe />').attr(attrs).addClass('ss-htmleditorfield-file iframe');

				$.each(extraData, function (key, value) {
					iframeEl.attr('data-' + key, value)
				});

				if(extraData.CaptionText) {
					el = $('<div style="width: ' + attrs['width'] + 'px;" class="captionImage ' + attrs['class'] + '"><p class="caption">' + extraData.CaptionText + '</p></div>').prepend(iframeEl);
				} else {
					el = iframeEl;
				}
				return $('<div />').append(el).html(); // Little hack to get outerHTML string
			},
			updateFromNode: function(node) {
				this.find(':input[name=Width]').val(node.width());
				this.find(':input[name=Height]').val(node.height());
				this.find(':input[name=CSSClass]').val(node.data('cssclass'));
			}
		});

		$('form.htmleditorfield-form.htmleditorfield-mediaform input.iframeurl').entwine({
			onadd: function() {
				this.validate();
			},

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
				var val = this.val(), orig = val;

				val = val.replace(/^https?:\/\//i, '');
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
					form.showIframeView('http://' + urlField.val()).complete(function() {
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