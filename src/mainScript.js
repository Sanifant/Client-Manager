import { generateUrl, imagePath } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

function main() {
	// we get the data injected via the Initial State mechanism
	const state = loadState('clientmanager', 'tutorial_initial_state')

	// this is the empty div from the template (/templates/myMainTemplate.php)
	const tutorialDiv = document.querySelector('#app-content #clientmanager')

	console.info("Test")
	console.info("UserId: " + state.userid)


	addClients(tutorialDiv, state)
	addConfigButton(tutorialDiv, state)

	// addGifs(tutorialDiv, state)
}

function addHeaders(table, keys) {
	var row = table.insertRow();
	for( var i = 0; i < keys.length; i++ ) {
	  var cell = row.insertCell();
	  cell.appendChild(document.createTextNode(keys[i]));
	}
  }

function addClients(container, state) {

	const clientList = state.clientList

	var table = document.createElement('table');
	for( var i = 0; i < clientList.length; i++ ) {

		var client = clientList[i];
		if(i === 0 ) {
			addHeaders(table, Object.keys(client));
		}
		var row = table.insertRow();
		Object.keys(client).forEach(function(k) {
			var cell = row.insertCell();
			cell.appendChild(document.createTextNode(client[k]));
		})
	}
		
	container.appendChild(table)
}

function addConfigButton(container, state) {
	// add a button to switch theme
	const themeButton = document.createElement('button')
	themeButton.innerText = state.fixed_gif_size === '1' ? 'Enable variable gif size' : 'Enable fixed gif size'
	if (state.fixed_gif_size === '1') {
		container.classList.add('fixed-size')
	}
	themeButton.addEventListener('click', (e) => {
		if (state.fixed_gif_size === '1') {
			state.fixed_gif_size = '0'
			themeButton.innerText = 'Enable fixed gif size'
			container.classList.remove('fixed-size')
		} else {
			state.fixed_gif_size = '1'
			themeButton.innerText = 'Enable variable gif size'
			container.classList.add('fixed-size')
		}
		const url = generateUrl('/apps/clientmanager/config')
		const params = {
			key: 'fixed_gif_size',
			value: state.fixed_gif_size,
		}
		axios.put(url, params)
			.then((response) => {
				showSuccess('Settings saved: ' + response.data.message)
			})
			.catch((error) => {
				showError('Failed to save settings: ' + error.response.data.error_message)
				console.error(error)
			})
	})
	container.append(themeButton)
}

// we wait for the page to be fully loaded
document.addEventListener('DOMContentLoaded', (event) => {
	main()
})