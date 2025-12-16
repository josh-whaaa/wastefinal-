<?php
// Fetch all routes for dropdown
$sql = "SELECT id, route_details FROM route_info ORDER BY id";
$result = $conn->query($sql);
$allRoutes = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="modal fade" id="editRouteModal" tabindex="-1" aria-labelledby="editRouteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRouteLabel">Select Route</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <select id="routeSelector" class="form-select" onchange="loadRouteDetails()">
                <option value="">Choose a route...</option>
                <option value="1">Route 1 (Market to Patyo Daan)</option>
                <option value="2">Route 2 (Church to Bag Ong Dalan)</option>
                <!-- <option value="3">Route 3 (Baungon to Bitoon)</option> -->
                <!-- <option value="4">Route 4 (Bitoon to Canepa)</option> -->
            </select>
        </div>
        <div class="mb-3">
            <label>Start Point</label>
            <input type="text" id="displayStartPoint" class="form-control" readonly>
        </div>
        <div class="mb-3">
            <label>End Point</label>
            <input type="text" id="displayEndPoint" class="form-control" readonly>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" onclick="viewSelectedRoute()">Done</button>
      </div>
    </div>
  </div>
</div>

<script>
// Route data storage
const routeData = {
    1: {
        startId: 1,
        endId: 2,
        startPoint: '',
        endPoint: '',
        startLat: '',
        startLong: '',
        endLat: '',
        endLong: ''
    },
    2: {
        startId: 3,
        endId: 4,
        startPoint: '',
        endPoint: '',
        startLat: '',
        startLong: '',
        endLat: '',
        endLong: ''
    },
    3: {
        startId: 5,
        endId: 6,
        startPoint: '',
        endPoint: '',
        startLat: '',
        startLong: '',
        endLat: '',
        endLong: ''
    },
    4: {
        startId: 7,
        endId: 8,
        startPoint: '',
        endPoint: '',
        startLat: '',
        startLong: '',
        endLat: '',
        endLong: ''
    }
};

// Function to update the table display with selected route
function updateTableDisplay(route) {
    // Find the table row and update the route display
    const tableRow = document.querySelector('tbody tr');
    if (tableRow) {
        const routeCell = tableRow.querySelector('td:nth-child(2) p');
        if (routeCell) {
            routeCell.innerHTML = `Start: ${route.startPoint} → End: ${route.endPoint}`;
        }
        
        // Update the view route button data attributes
        const viewButton = tableRow.querySelector('.view-route');
        if (viewButton) {
            viewButton.setAttribute('data-start-lat', route.startLat);
            viewButton.setAttribute('data-start-long', route.startLong);
            viewButton.setAttribute('data-end-lat', route.endLat);
            viewButton.setAttribute('data-end-long', route.endLong);
            viewButton.setAttribute('data-start-point', route.startPoint);
            viewButton.setAttribute('data-end-point', route.endPoint);
        }
        
        // Update the edit route button data attributes
        const editButton = tableRow.querySelector('.edit-route-btn');
        if (editButton) {
            editButton.setAttribute('data-start-id', route.startId);
            editButton.setAttribute('data-end-id', route.endId);
            editButton.setAttribute('data-start-point', route.startPoint);
            editButton.setAttribute('data-end-point', route.endPoint);
            editButton.setAttribute('data-start-lat', route.startLat);
            editButton.setAttribute('data-start-long', route.startLong);
            editButton.setAttribute('data-end-lat', route.endLat);
            editButton.setAttribute('data-end-long', route.endLong);
        }
    }
}

// Function to save route selection to database
function saveRouteSelection(selectedRoute) {
    const formData = new FormData();
    formData.append('action', 'update_route');
    formData.append('route_number', selectedRoute);
    
    fetch('../api/update_route_selection.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Route selection saved to database');
        } else {
            console.error('Error saving route selection:', data.error);
        }
    })
    .catch(error => {
        console.error('Error saving route selection:', error);
    });
}

function loadRouteDetails() {
    const selectedRoute = document.getElementById('routeSelector').value;
    
    console.log('Selected route:', selectedRoute);
    
    if (!selectedRoute) {
        // Clear fields if no route selected
        document.getElementById('displayStartPoint').value = '';
        document.getElementById('displayEndPoint').value = '';
        return;
    }
    
    // Show loading state
    document.getElementById('displayStartPoint').value = 'Loading...';
    document.getElementById('displayEndPoint').value = 'Loading...';
    
    // Fetch route details from database
    fetch(`../api/get_route_details.php?route=${selectedRoute}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            
            if (data.success) {
                // Update display fields
                document.getElementById('displayStartPoint').value = data.startPoint || 'Not found';
                document.getElementById('displayEndPoint').value = data.endPoint || 'Not found';
                
                // Store data for viewing
                routeData[selectedRoute] = {
                    startId: data.startId,
                    endId: data.endId,
                    startPoint: data.startPoint,
                    endPoint: data.endPoint,
                    startLat: data.startLat,
                    startLong: data.startLong,
                    endLat: data.endLat,
                    endLong: data.endLong
                };
                
                console.log('Route data stored:', routeData[selectedRoute]);
            } else {
                console.error('API Error:', data.error);
                document.getElementById('displayStartPoint').value = 'Error: ' + (data.error || 'Unknown error');
                document.getElementById('displayEndPoint').value = 'Error: ' + (data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            document.getElementById('displayStartPoint').value = 'Error loading data';
            document.getElementById('displayEndPoint').value = 'Error loading data';
            
            // Show error message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Route',
                    text: 'Could not load route details. Please try again.',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            }
        });
}

function viewSelectedRoute() {
    const selectedRoute = document.getElementById('routeSelector').value;
    
    if (!selectedRoute || !routeData[selectedRoute]) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'No Route Selected',
                text: 'Please select a route first',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Please select a route first');
        }
        return;
    }
    
    const route = routeData[selectedRoute];
    
    // Check if coordinates exist
    if (!route.startLat || !route.startLong || !route.endLat || !route.endLong) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Coordinates Not Found',
                text: 'Coordinates not found for selected route',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Coordinates not found for selected route');
        }
        return;
    }
    
    // Show loading message
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Loading Route...',
            text: 'Please wait while we display the route on the map',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editRouteModal'));
    modal.hide();
    
    // Trigger route view on map
    if (typeof map !== 'undefined') {
        // Remove any existing polygons
        Object.values(barangayPolygons).forEach(poly => {
            if (map.hasLayer(poly)) map.removeLayer(poly);
        });
        
        // Create start and end points
        const startCoords = [parseFloat(route.startLat), parseFloat(route.startLong)];
        const endCoords = [parseFloat(route.endLat), parseFloat(route.endLong)];
        
        // Add markers for start and end points
        const startMarker = L.marker(startCoords).addTo(map);
        const endMarker = L.marker(endCoords).addTo(map);
        
        startMarker.bindPopup(`<b>Start:</b> ${route.startPoint}`).openPopup();
        endMarker.bindPopup(`<b>End:</b> ${route.endPoint}`);
        
        // Add a simple line between start and end points
        const routeLine = L.polyline([startCoords, endCoords], {
            color: 'blue',
            weight: 4,
            opacity: 0.8
        }).addTo(map);
        
        // Fit bounds to show both points
        const group = new L.featureGroup([startMarker, endMarker, routeLine]);
        map.fitBounds(group.getBounds().pad(0.1));
        
        // Update the table display with the selected route
        updateTableDisplay(route);
        
        // Save the route selection to database
        saveRouteSelection(selectedRoute);
        
        // Clear loading state and show success message
        document.getElementById('displayStartPoint').value = route.startPoint;
        document.getElementById('displayEndPoint').value = route.endPoint;
        
        // Show success message immediately
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Edit Complete!',
                text: `Route successfully selected and saved: "${route.startPoint}" → "${route.endPoint}"`,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Perfect!'
            });
        }
        
    } else {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Map Not Available',
                text: 'Map is not loaded. Please refresh the page.',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Map not available');
        }
    }
}
</script>
