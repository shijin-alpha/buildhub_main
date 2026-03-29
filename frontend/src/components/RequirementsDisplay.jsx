import React from 'react';

const RequirementsDisplay = ({ requirements, compact = false }) => {
  // Parse requirements if it's a string
  let parsedRequirements = requirements;
  if (typeof requirements === 'string') {
    try {
      parsedRequirements = JSON.parse(requirements);
    } catch (e) {
      // If it's not valid JSON, treat as plain text
      return (
        <div className="requirements-display">
          <p>{requirements}</p>
        </div>
      );
    }
  }

  if (!parsedRequirements || typeof parsedRequirements !== 'object') {
    return null;
  }

  const formatRoomName = (roomName) => {
    return roomName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  };

  const renderFloorRooms = (floorRooms) => {
    if (!floorRooms) return null;

    return (
      <div className="floor-rooms">
        {Object.entries(floorRooms).map(([floorKey, rooms]) => {
          const floorNumber = floorKey.replace('floor', '');
          const floorName = floorNumber === '1' ? 'Ground Floor' : `Floor ${floorNumber}`;
          
          return (
            <div key={floorKey} className="floor-section" style={{ marginBottom: '12px' }}>
              <h6 style={{ 
                margin: '0 0 8px 0', 
                fontSize: '0.85rem', 
                fontWeight: '600', 
                color: '#374151',
                borderBottom: '1px solid #e5e7eb',
                paddingBottom: '4px'
              }}>
                {floorName}
              </h6>
              <div className="room-tags" style={{ 
                display: 'flex', 
                flexWrap: 'wrap', 
                gap: '4px',
                marginLeft: '8px'
              }}>
                {Object.entries(rooms || {}).map(([roomType, count]) => {
                  if (!count || count === 0) return null;
                  return (
                    <span
                      key={roomType}
                      style={{
                        display: 'inline-block',
                        margin: '2px 4px 2px 0',
                        padding: '3px 6px',
                        backgroundColor: '#dbeafe',
                        color: '#1e40af',
                        borderRadius: '8px',
                        fontSize: '11px',
                        fontWeight: '500'
                      }}
                    >
                      {formatRoomName(roomType)}: {count}
                    </span>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
    );
  };

  const renderBasicInfo = () => (
    <div className="basic-info" style={{ marginBottom: '16px' }}>
      {parsedRequirements.plot_shape && (
        <p style={{ margin: '4px 0', fontSize: '0.9rem' }}>
          <strong>Plot Shape:</strong> {formatRoomName(parsedRequirements.plot_shape)}
        </p>
      )}
      {parsedRequirements.topography && (
        <p style={{ margin: '4px 0', fontSize: '0.9rem' }}>
          <strong>Topography:</strong> {formatRoomName(parsedRequirements.topography)}
        </p>
      )}
      {parsedRequirements.num_floors && (
        <p style={{ margin: '4px 0', fontSize: '0.9rem' }}>
          <strong>Floors:</strong> {parsedRequirements.num_floors}
        </p>
      )}
      {parsedRequirements.preferred_style && (
        <p style={{ margin: '4px 0', fontSize: '0.9rem' }}>
          <strong>Style:</strong> {formatRoomName(parsedRequirements.preferred_style)}
        </p>
      )}
      {parsedRequirements.aesthetic && (
        <p style={{ margin: '4px 0', fontSize: '0.9rem' }}>
          <strong>Aesthetic:</strong> {formatRoomName(parsedRequirements.aesthetic)}
        </p>
      )}
    </div>
  );

  const renderRoomsFromString = () => {
    if (!parsedRequirements.rooms || typeof parsedRequirements.rooms !== 'string') return null;
    
    const roomsList = parsedRequirements.rooms.split(',').map(room => room.trim());
    
    return (
      <div className="rooms-from-string" style={{ marginBottom: '12px' }}>
        <p style={{ margin: '0 0 8px 0', fontWeight: '600', fontSize: '0.9rem' }}>• Rooms:</p>
        <div style={{ marginLeft: '10px' }}>
          {roomsList.map((room, index) => (
            <span
              key={index}
              style={{
                display: 'inline-block',
                margin: '2px 4px 2px 0',
                padding: '3px 6px',
                backgroundColor: '#dbeafe',
                color: '#1e40af',
                borderRadius: '8px',
                fontSize: '11px',
                fontWeight: '500'
              }}
            >
              {formatRoomName(room)}: 1
            </span>
          ))}
        </div>
      </div>
    );
  };

  const renderFamilyNeeds = () => {
    if (!parsedRequirements.family_needs) return null;
    
    return (
      <div className="family-needs" style={{ marginBottom: '12px' }}>
        <p style={{ margin: '0 0 8px 0', fontWeight: '600', fontSize: '0.9rem' }}>• Family Needs:</p>
        <div style={{ marginLeft: '10px' }}>
          <span style={{
            display: 'inline-block',
            margin: '2px 4px 2px 0',
            padding: '3px 6px',
            backgroundColor: '#f3e8ff',
            color: '#7c3aed',
            borderRadius: '8px',
            fontSize: '11px',
            fontWeight: '500'
          }}>
            {parsedRequirements.family_needs}
          </span>
        </div>
      </div>
    );
  };

  const renderNotes = () => {
    if (!parsedRequirements.notes) return null;
    
    return (
      <div className="notes" style={{ marginBottom: '12px' }}>
        <p style={{ margin: '0 0 8px 0', fontWeight: '600', fontSize: '0.9rem' }}>• Additional Notes:</p>
        <p style={{ 
          margin: '0', 
          fontSize: '0.85rem', 
          color: '#6b7280',
          fontStyle: 'italic',
          marginLeft: '10px'
        }}>
          {parsedRequirements.notes}
        </p>
      </div>
    );
  };

  if (compact) {
    return (
      <div className="requirements-display compact" style={{ 
        fontSize: '0.9rem', 
        color: '#666',
        marginLeft: '10px'
      }}>
        {parsedRequirements.floor_rooms && renderFloorRooms(parsedRequirements.floor_rooms)}
        {!parsedRequirements.floor_rooms && renderRoomsFromString()}
        {renderBasicInfo()}
        {renderFamilyNeeds()}
        {renderNotes()}
      </div>
    );
  }

  return (
    <div className="requirements-display" style={{ 
      padding: '16px',
      backgroundColor: '#f8fafc',
      borderRadius: '8px',
      border: '1px solid #e2e8f0'
    }}>
      <h5 style={{ margin: '0 0 12px 0', color: '#374151' }}>Requirements:</h5>
      {parsedRequirements.floor_rooms && renderFloorRooms(parsedRequirements.floor_rooms)}
      {!parsedRequirements.floor_rooms && renderRoomsFromString()}
      {renderBasicInfo()}
      {renderFamilyNeeds()}
      {renderNotes()}
    </div>
  );
};

export default RequirementsDisplay;