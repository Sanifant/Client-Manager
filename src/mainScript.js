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
	const table = document.createElement('table')
	var headers = ["ID", "Name", "Content"];
	
	for(var i = 0; i < clientList.length; i++) {
        var row = table.insertRow(i);
        row.insertCell(0).innerHTML = clientList[i].id;
        row.insertCell(1).innerHTML = clientList[i].name;
        row.insertCell(2).innerHTML = clientList[i].content;
    }

    var header = table.createTHead();
    var headerRow = header.insertRow(0);
    for(var i = 0; i < headers.length; i++) {
        headerRow.insertCell(i).innerHTML = headers[i];
    }
		
	container.appendChild(table)
}

function addGifs(container, state) {
	const fileNameList = state.file_name_list
	// for each file, we create a div which contains a button and an image
	fileNameList.forEach(name => {
		const fileDiv = document.createElement('div')
		fileDiv.classList.add('gif-wrapper')

		const img = document.createElement('img')
		img.setAttribute('src', imagePath('clientmanager', 'gifs/' + name))
		img.style.display = 'none'

		// the button toggles the image visibility
		const button = document.createElement('button')
		button.innerText = 'Show/hide ' + name
		button.addEventListener('click', (e) => {
			if (img.style.display === 'block') {
				img.style.display = 'none'
			} else {
				img.style.display = 'block'
			}
		})

		fileDiv.append(button)
		fileDiv.append(img)
		container.append(fileDiv)
	})
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