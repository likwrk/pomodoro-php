'use strict';
const Pomodoro = {
	api: 'http://pomodoro.dev/api',
	request: function(method, url, data) {
		const request = {
			method: method,
			headers: this.getHeaders()
		};
		if (data) request.body = JSON.stringify(data);
		return fetch(this.api + url, request)
		.then(function(response) { return response.json(); })
		.catch(error => {
			console.log(error);
		});
	},
	getHeaders: function() {
		const headers = new Headers();
		headers.append("Content-Type", "application/json");
		headers.append("Accept", "application/json");
		return headers;
	},
	updateComment: function(element) {
		const form = element.tagName === 'FORM' ? element : element.parentElement;
		const id = form.getAttribute('data-timer');
		const commentInput = form.getElementsByClassName('comment')[0];
		const comment = commentInput.value;
		this.request('PUT', `/timers/${id}`, {comment}).then(timer => {
			commentInput.value = timer.comment;
		});
	},
	updateLogged: function(checkbox) {
		const id = checkbox.getAttribute('data-timer');
		const logged = checkbox.checked;
		this.request('PUT', `/timers/${id}`, {logged}).then(timer => {
			checkbox.checked = !!timer.logged;
		});
	}
};
