//==============================================================================
// single-use-module-boilerplate.js
//
// Boilerplate for single purpose JS object
// Great for single page scripts
//==============================================================================

"use strict";

var topics = (function ($, window, document, undefined) {
	//--------------------------------------------------------------------------
	// Private
	//

	// --- Properties ---
	var my = {},
		scriptRegEx = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script\s*>/gi,
		hexCodeRegEx = /#([0-9a-fA-F]{3}){1,2}/gi,
		counters = {
			topics: 0,
			active: 0,
			inactive: 0,
			saving: 0,
			edited: 0,
		},
		saveAllBtn,
		topicList,
		topicCounterDisplay,
		activeCounterDisplay,
		hideInactive = false,
		drake;

	// --- Methods ---

	function onAjaxFailure(jqXHR, status, error) {
		window.alert(
			"Unable to process request, contact development team. See console for details."
		);
		console.error(jqXHR, status, error);
	}

	function colorFindInverse(hexcode) {
		if (hexcode.length === 7) {
			hexcode = hexcode.substring(1);
		} else if (hexcode.length !== 6) {
			console.warn("Invalid hex string: " + hexcode);
			return "white";
		}
		var rgb = [
			parseInt(hexcode.substring(0, 2), 16),
			parseInt(hexcode.substring(2, 4), 16),
			parseInt(hexcode.substring(4), 16),
		];
		var o = Math.round((rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000);
		return o > 175 ? "black" : "white";
	}

	var showColorChange = _.throttle(
		function (e) {
			// jshint ignore:line
			var color = $(e.currentTarget).val(),
				indicator = $(e.currentTarget)
					.closest(".topic")
					.find(".color-indicator");
			if (color.match(hexCodeRegEx)) {
				indicator.css({ background: color });
			} else {
				indicator.css({ background: "#ebebeb" });
			}
		},
		200,
		{ leading: false, trailing: true }
	);

	function updateUI() {
		counters.topics = topicList.children(":not(.new)").length;
		counters.active = topicList.find(".topic-active-toggle.is-primary").length;
		counters.inactive = counters.topics - counters.active;
		topicCounterDisplay.text(counters.topics);
		activeCounterDisplay.text(
			hideInactive ? counters.inactive : counters.active
		);
		$(".active-descriptor").text(hideInactive ? "hidden" : "active");
	}

	function deleteTopicHandler(e) {
		var button = $(e.currentTarget),
			topic = $(button).closest(".topic"),
			topicId = parseInt(topic.attr("id").substring("topic-".length));
		button.addClass("is-loading");
		button.blur();
		if (!window.confirm("This topic will not be recoverable if deleted")) {
			button.removeClass("is-loading");
			button.focus();
			return;
		}
		if (topicId === 0) {
			topic.slideUp().remove();
		} else {
			$.ajax({
				url: e.currentTarget.getAttribute("data-action"),
				dataType: "json",
				method: "DELETE",
				context: {
					button: button,
					topic: topic,
				},
			})
				.always(function () {
					this.button.removeClass("is-loading");
				})
				.done(function (data, status, jqXHR) {
					this.topic.slideUp().remove();
				})
				.fail(onAjaxFailure);
		}
	}

	function deleteKeywordHandler(e) {
		$(e.currentTarget).closest(".topic-keyword").remove();
	}

	function addKeywordHandler(e) {
		var button = $(e.currentTarget);
		button.addClass("is-loading");
		$.ajax({
			url: e.currentTarget.getAttribute("data-action"),
			method: "GET",
			context: button,
		})
			.always(function () {
				this.removeClass("is-loading");
			})
			.done(function (data, status, jqXHR) {
				data = data.replace(scriptRegEx, "");
				$($.parseHTML(data)).insertBefore(this);
				configureTopic(this.closest(".topic"));
			})
			.fail(onAjaxFailure);
	}

	function editTopicHandler(e) {
		var button = $(e.currentTarget);
		button.addClass("is-loading");
		$.ajax({
			url: e.currentTarget.getAttribute("data-action"),
			method: "GET",
			context: button,
		})
			.always(function () {
				button.removeClass("is-loading");
			})
			.done(function (data, status, jqXHR) {
				var topic = button.closest(".topic");
				data = data.replace(scriptRegEx, "");
				var renderedTopic = $($.parseHTML(data));
				topic.replaceWith(renderedTopic);
				configureTopic(renderedTopic);
			})
			.fail(onAjaxFailure);
	}

	function newTopicHandler(e) {
		var button = $(e.currentTarget);
		button.addClass("is-loading");
		button.blur();
		$.ajax({
			url: button.attr("data-action"),
			method: "GET",
			context: button,
		})
			.always(function () {
				button.removeClass("is-loading");
			})
			.done(function (data, status, jqXHR) {
				data = data.replace(scriptRegEx, "");
				var renderedTopic = $($.parseHTML(data));
				topicList.append(renderedTopic);
				configureTopic(renderedTopic);
				$("html, body").animate(
					{
						scrollTop: renderedTopic.offset().top,
					},
					1250
				);
				$(renderedTopic).find('input[name="topic"]').focus();
			})
			.fail(onAjaxFailure);
	}

	function resetTopicHandler(e) {
		var button = $(e.currentTarget),
			topic = button.closest(".topic"),
			topicId = parseInt(topic.attr("id").substring("topic-".length));
		if (topicId === 0) {
			topic.fadeOut().remove();
		} else {
			var form = topic.find(".topic-edit");
			form.addClass("is-pending-removal");
			button.addClass("is-loading");
			$.ajax({
				url: button.attr("data-action"),
				method: "GET",
				context: { topic: topic, button: button },
			})
				.always(function () {
					this.button.removeClass("is-loading");
				})
				.done(function (data, status, jqXHR) {
					data = data.replace(scriptRegEx, "");
					var renderedTopic = $($.parseHTML(data));
					this.topic.replaceWith(renderedTopic);
					configureTopic(renderedTopic);
				})
				.fail(onAjaxFailure);
		}
	}

	function saveTopicHandler(e) {
		e.preventDefault();
		var button = $(e.currentTarget),
			topic = button.closest(".topic"),
			topicId = parseInt(topic.attr("id").substring("topic-".length)),
			form = topic.find(".topic-edit")[0],
			errorsEncountered = false,
			postData = {
				topic: form.topic.value,
				active: form.active.checked,
				color: form.color.value,
				rank: form.rank.value,
				keywords: [],
				custom_order: topic.index(), // jshint ignore:line
			};
		if (!form.topic.value) {
			errorsEncountered = true;
			$(form.topic).addClass("is-danger").focus();
		}
		if (!postData.color.match(hexCodeRegEx)) {
			$(form.color).addClass("is-danger");
		} else if (postData.color.length === 4) {
			var fullcolor = "#";
			for (var i = 1; i < 4; i++) {
				fullcolor += postData.color[i] + postData.color[i];
			}
			postData.color = fullcolor;
		}
		$(form)
			.find(".topic-keyword")
			.each(function (i, elem) {
				var keyword = $(elem).find(".keyword-keyword"),
					priority = $(elem).find(".keyword-priority");
				if (!keyword[0].value) {
					errorsEncountered = true;
					keyword.addClass("is-danger");
				}
				postData.keywords.push({
					keyword: keyword[0].value,
					priority: priority[0].value,
				});
			});
		if (errorsEncountered) {
			return;
		}
		counters.saving++;
		button.addClass("is-loading");
		button.siblings("button.button").attr("disabled", "disabled");
		$.ajax({
			url: button.attr("data-action"),
			dataType: "html",
			method: "POST",
			data: postData,
			context: {
				button: button,
				topic: topic,
			},
		})
			.always(function () {
				this.button.removeClass("is-loading");
				counters.saving--;
				if (counters.saving < 1) {
					counters.saving = 0;
					$(".save-all-btn").removeClass("is-loading");
				}
			})
			.done(function (data, status, jqXHR) {
				data = data.replace(scriptRegEx, "");
				var renderedTopic = $($.parseHTML(data));
				this.topic.replaceWith(renderedTopic);
				configureTopic(renderedTopic);
				saveRearranged();
				updateUI();
			})
			.fail(onAjaxFailure);
	}

	function toggleActiveHandler(e) {
		e.preventDefault();
		var button = $(e.currentTarget),
			currentStatus = button.hasClass("is-primary");
		button.addClass("is-loading");
		$.ajax({
			url: button.attr("data-action"),
			contentType: "application/json",
			dataType: "json",
			method: "PATCH",
			data: JSON.stringify({ active: !currentStatus }),
			processData: false,
			context: e.currentTarget,
		})
			.always(function () {
				$(this).removeClass("is-loading");
			})
			.done(function (data, status, jqXHR) {
				console.log(data);
				if (!!data.active) {
					$(this).addClass("is-primary").find(".topic-active").text("Active");
				} else {
					$(this)
						.removeClass("is-primary")
						.find(".topic-active")
						.text("Inactive");
					if (hideInactive) {
						$(this).closest(".topic").slideUp();
					}
				}
				updateUI();
			})
			.fail(onAjaxFailure);
	}

	function configureTopic(topic) {
		if (!(topic instanceof jQuery)) {
			topic = $(topic);
		}
		topic
			.find(".topic-active-toggle")
			.off("click")
			.on("click", toggleActiveHandler);
		topic
			.find(".topic-delete-keyword-btn")
			.off("click")
			.on("click", deleteKeywordHandler);
		topic
			.find(".topic-add-keyword-btn")
			.off("click")
			.on("click", addKeywordHandler);
		topic
			.find(".topic-delete-btn")
			.off("click")
			.on("click", deleteTopicHandler);
		topic.find(".topic-edit-btn").off("click").on("click", editTopicHandler);
		topic.find(".topic-reset-btn").off("click").on("click", resetTopicHandler);
		topic.find(".topic-save-btn").off("click").on("click", saveTopicHandler);
		topic.find(".topic-color.tag").each(function (i, elem) {
			elem.style.backgroundColor = $(elem).text();
			elem.style.color = colorFindInverse($(elem).text());
		});
		topic
			.find('.input[name="color"]')
			.off("keyup")
			.on("keyup", showColorChange)
			.trigger("keyup");
	}

	var saveRearranged = _.debounce(
		function () {
			// jshint ignore:line
			var newOrder = [];
			$(".topic:not(.new, .gu-mirror)").each(function (i, elem) {
				var topic = $(elem),
					topicId = parseInt(topic.attr("id").substring("topic-".length));
				newOrder.push({
					id: topicId,
					order: topic.index(),
				});
			});
			$.ajax({
				url: topicList.attr("data-action"),
				contentType: "application/json",
				method: "PATCH",
				data: JSON.stringify(newOrder), // jshint ignore:line
				processData: false,
			})
				.done(function (data, status, jqxhr) {
					console.log("reorder: " + status);
				})
				.fail(onAjaxFailure);
		},
		1200,
		{ leading: false, trailing: true }
	);

	// --- Document Ready ---
	$(function () {
		saveAllBtn = $(".save-all-btn");
		topicList = $("#topic-list");
		topicCounterDisplay = $("#topic-counter");
		activeCounterDisplay = $("#active-counter");

		drake = dragula([topicList[0]]); //jshint ignore:line
		drake.on("drop", function (el, target, source, sibling) {
			if (!$(el).hasClass("is-being-edited")) {
				saveRearranged();
			}
		});

		$(".topic").each(function (i, elem) {
			configureTopic($(elem));
		});
		$(".add-new-btn").on("click", newTopicHandler);
		saveAllBtn.on("click", function (e) {
			var editedTopics = $(".topic.is-being-edited");
			if (editedTopics.length > 0) {
				$(this).addClass("is-loading");
			}
			editedTopics.each(function (i, elem) {
				console.log("click");
				$(elem).find(".topic-save-btn").trigger("click");
			});
		});

		$("#toggle-inactive-btn").on("click", function (e) {
			var inactiveToggles = $(".topic-active-toggle:not(.is-primary)");
			inactiveToggles.closest(".topic").fadeToggle();
			$(this).toggleClass("is-outlined");
			hideInactive = !hideInactive;
			updateUI();
			$(this).blur();
		});
	});

	//--------------------------------------------------------------------------
	// Public
	//

	// --- Properties ---
	my.publicVar = "World";

	// --- Methods ---
	my.publicFunc = function () {
		// ...
	};

	return my;
})(jQuery, window, document, undefined);
