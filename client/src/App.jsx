import { useEffect, useState } from 'react'
import './App.css'

const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

const ALERT_INFO = {
  frost: { label: 'Geada', icon: '❄️' },
  heavy_rain: { label: 'Chuva forte', icon: '🌧️' },
  drought: { label: 'Seca', icon: '☀️' },
}

function formatDate(isoDate) {
  const [year, month, day] = isoDate.split('-')
  return `${day}/${month}/${year}`
}

function useTheme() {
  const [theme, setTheme] = useState(() => {
    const stored = localStorage.getItem('theme')
    if (stored === 'light' || stored === 'dark') {
      return stored
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  })

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme)
    localStorage.setItem('theme', theme)
  }, [theme])

  return [theme, setTheme]
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
    <form className="location-form" onSubmit={handleSubmit}>
      <label className="field">
        Latitude
        <input
          value={lat}
          onChange={(event) => setLat(event.target.value)}
          type="number"
          step="any"
          placeholder="38.71"
          required
        />
      </label>
      <label className="field">
        Longitude
        <input
          value={lon}
          onChange={(event) => setLon(event.target.value)}
          type="number"
          step="any"
          placeholder="-9.14"
          required
        />
      </label>
      <button className="primary-button" type="submit">
        Ver previsão
      </button>
    </form>
  )
}

function ForecastTable({ daily }) {
  return (
    <div className="table-wrapper">
      <table className="forecast-table">
        <thead>
          <tr>
            <th scope="col">Dia</th>
            <th scope="col">Mín (°C)</th>
            <th scope="col">Máx (°C)</th>
            <th scope="col">Precipitação (mm)</th>
          </tr>
        </thead>
        <tbody>
          {daily.time.map((date, index) => (
            <tr key={date}>
              <td>{formatDate(date)}</td>
              <td>{daily.temperature_2m_min?.[index] ?? '—'}</td>
              <td>{daily.temperature_2m_max?.[index] ?? '—'}</td>
              <td>{daily.precipitation_sum?.[index] ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function AlertsList({ alerts }) {
  if (alerts.length === 0) {
    return <p className="empty-alerts">Sem alertas para os próximos dias.</p>
  }

  return (
    <ul className="alerts-list">
      {alerts.map((alert) => {
        const info = ALERT_INFO[alert.type] ?? { label: alert.type, icon: '⚠️' }
        const period =
          alert.start === alert.end
            ? formatDate(alert.start)
            : `${formatDate(alert.start)} a ${formatDate(alert.end)}`

        return (
          <li key={`${alert.type}-${alert.start}`} className={`alert-item alert-item--${alert.type}`}>
            <span className="alert-item__icon" aria-hidden="true">
              {info.icon}
            </span>
            <span className="alert-item__label">{info.label}</span>
            <span>{period}</span>
          </li>
        )
      })}
    </ul>
  )
}

function App() {
  const [theme, setTheme] = useTheme()
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
    <div className="app-shell">
      <header className="app-header">
        <div className="heading-block">
          <h1>Alertas Meteorológicos</h1>
          <p className="tagline">Previsão dos próximos dias e alertas de geada, chuva forte e seca</p>
        </div>
        <button
          className="theme-toggle"
          type="button"
          onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
          aria-label="Alternar entre tema claro e escuro"
        >
          {theme === 'dark' ? '☀️ Claro' : '🌙 Escuro'}
        </button>
      </header>

      {needsManualInput && (
        <section className="card">
          {geoMessage && <p className="status-message">{geoMessage}</p>}
          <ManualLocationForm onSubmit={setCoords} />
        </section>
      )}

      {!needsManualInput && !coords && <p className="status-message">A obter localização…</p>}

      {loading && <p className="status-message">A carregar previsão…</p>}

      {fetchError && (
        <p className="error-message" role="alert">
          {fetchError}
        </p>
      )}

      {forecast?.stale && (
        <p className="stale-banner" role="status">
          {forecast.error}
        </p>
      )}

      {forecast?.daily?.time?.length > 0 && (
        <>
          <section className="card">
            <h2>Previsão</h2>
            <ForecastTable daily={forecast.daily} />
          </section>

          <section className="card">
            <h2>Alertas</h2>
            <AlertsList alerts={forecast.alerts ?? []} />
          </section>
        </>
      )}

      {coords && !needsManualInput && (
        <button className="secondary-button" type="button" onClick={() => setNeedsManualInput(true)}>
          Indicar outra localização
        </button>
      )}

      <footer className="app-footer">Dados meteorológicos: Open-Meteo.com</footer>
    </div>
  )
}

export default App
