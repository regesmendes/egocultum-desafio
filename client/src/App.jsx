import { useEffect, useState } from 'react'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const ALERT_LABELS = {
  frost: 'Geada',
  heavy_rain: 'Chuva forte',
  drought: 'Seca',
}

function useGeolocation() {
  const [coords, setCoords] = useState(null)
  const [needsManualInput, setNeedsManualInput] = useState(false)
  const [geoMessage, setGeoMessage] = useState(null)

  useEffect(() => {
    if (!navigator.geolocation) {
      setNeedsManualInput(true)
      setGeoMessage('Este browser não suporta geolocalização. Introduz as coordenadas manualmente.')
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setCoords({ lat: position.coords.latitude, lon: position.coords.longitude })
      },
      () => {
        setNeedsManualInput(true)
        setGeoMessage('Não foi possível obter a localização automaticamente. Introduz as coordenadas manualmente.')
      },
      { timeout: 10_000 },
    )
  }, [])

  return { coords, setCoords, needsManualInput, setNeedsManualInput, geoMessage }
}

function ManualLocationForm({ onSubmit }) {
  const [lat, setLat] = useState('')
  const [lon, setLon] = useState('')

  function handleSubmit(event) {
    event.preventDefault()
    const latNumber = Number.parseFloat(lat)
    const lonNumber = Number.parseFloat(lon)
    if (Number.isNaN(latNumber) || Number.isNaN(lonNumber)) {
      return
    }
    onSubmit({ lat: latNumber, lon: lonNumber })
  }

  return (
    <form onSubmit={handleSubmit}>
      <label>
        Latitude
        <input
          value={lat}
          onChange={(event) => setLat(event.target.value)}
          type="number"
          step="any"
          required
        />
      </label>
      <label>
        Longitude
        <input
          value={lon}
          onChange={(event) => setLon(event.target.value)}
          type="number"
          step="any"
          required
        />
      </label>
      <button type="submit">Ver previsão</button>
    </form>
  )
}

function ForecastTable({ daily }) {
  return (
    <table>
      <thead>
        <tr>
          <th>Dia</th>
          <th>Mín (°C)</th>
          <th>Máx (°C)</th>
          <th>Precipitação (mm)</th>
        </tr>
      </thead>
      <tbody>
        {daily.time.map((date, index) => (
          <tr key={date}>
            <td>{date}</td>
            <td>{daily.temperature_2m_min?.[index] ?? '—'}</td>
            <td>{daily.temperature_2m_max?.[index] ?? '—'}</td>
            <td>{daily.precipitation_sum?.[index] ?? '—'}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

function AlertsList({ alerts }) {
  if (alerts.length === 0) {
    return <p>Sem alertas para os próximos dias.</p>
  }

  return (
    <ul>
      {alerts.map((alert) => (
        <li key={`${alert.type}-${alert.start}`}>
          {ALERT_LABELS[alert.type] ?? alert.type}
          {': '}
          {alert.start === alert.end ? alert.start : `${alert.start} a ${alert.end}`}
        </li>
      ))}
    </ul>
  )
}

function App() {
  const { coords, setCoords, needsManualInput, setNeedsManualInput, geoMessage } = useGeolocation()
  const [forecast, setForecast] = useState(null)
  const [loading, setLoading] = useState(false)
  const [fetchError, setFetchError] = useState(null)

  useEffect(() => {
    if (!coords) {
      return
    }

    setLoading(true)
    setFetchError(null)

    fetch(`${API_URL}/api/forecast?lat=${coords.lat}&lon=${coords.lon}`)
      .then((response) => response.json())
      .then((data) => {
        const hasDailyData = Boolean(data.daily?.time?.length)
        if (data.error && !hasDailyData) {
          setFetchError(data.error)
          setForecast(null)
          return
        }
        setForecast(data)
      })
      .catch(() => {
        setFetchError('Não foi possível contactar o servidor. Tenta novamente mais tarde.')
      })
      .finally(() => setLoading(false))
  }, [coords])

  return (
    <main>
      <h1>Alertas Meteorológicos</h1>

      {needsManualInput && (
        <section>
          {geoMessage && <p>{geoMessage}</p>}
          <ManualLocationForm onSubmit={setCoords} />
        </section>
      )}

      {!needsManualInput && !coords && <p>A obter localização…</p>}

      {loading && <p>A carregar previsão…</p>}

      {fetchError && <p role="alert">{fetchError}</p>}

      {forecast?.stale && <p role="status">{forecast.error}</p>}

      {forecast?.daily?.time?.length > 0 && (
        <section>
          <ForecastTable daily={forecast.daily} />
          <h2>Alertas</h2>
          <AlertsList alerts={forecast.alerts ?? []} />
        </section>
      )}

      {coords && !needsManualInput && (
        <button type="button" onClick={() => setNeedsManualInput(true)}>
          Indicar outra localização
        </button>
      )}

      <footer>Dados meteorológicos: Open-Meteo.com</footer>
    </main>
  )
}

export default App
