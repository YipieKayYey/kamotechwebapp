import React, { Component, ErrorInfo, ReactNode } from 'react';
import { AlertCircle } from 'lucide-react';

interface Props {
  children: ReactNode;
  fallbackMessage?: string;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
}

export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    };
  }

  static getDerivedStateFromError(error: Error): State {
    return {
      hasError: true,
      error,
      errorInfo: null
    };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    
    // Log to console for debugging
    console.error('Error details:', {
      message: error.message,
      stack: error.stack,
      componentStack: errorInfo.componentStack
    });

    this.setState({
      error,
      errorInfo
    });
  }

  handleReset = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null
    });
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="error-boundary-container">
          <div className="error-boundary-content">
            <div className="error-icon">
              <AlertCircle size={48} className="text-red-500" />
            </div>
            <h2 className="error-title">
              {this.props.fallbackMessage || 'Something went wrong'}
            </h2>
            <p className="error-message">
              We're sorry for the inconvenience. Please try refreshing the page.
            </p>
            {process.env.NODE_ENV === 'development' && this.state.error && (
              <details className="error-details">
                <summary>Error Details (Development Only)</summary>
                <pre className="error-stack">
                  {this.state.error.toString()}
                  {this.state.errorInfo && this.state.errorInfo.componentStack}
                </pre>
              </details>
            )}
            <div className="error-actions">
              <button
                onClick={() => window.location.reload()}
                className="error-reload-button"
              >
                Refresh Page
              </button>
              <button
                onClick={this.handleReset}
                className="error-retry-button"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Hook for using error boundary
export function useErrorHandler() {
  return (error: Error) => {
    console.error('Manual error:', error);
    throw error; // This will be caught by the nearest ErrorBoundary
  };
}

// CSS styles for error boundary (add to app.css)
const errorBoundaryStyles = `
.error-boundary-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 2rem;
}

.error-boundary-content {
  text-align: center;
  max-width: 500px;
}

.error-icon {
  margin-bottom: 1.5rem;
}

.error-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.75rem;
}

.error-message {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.error-details {
  background-color: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.error-stack {
  font-size: 0.75rem;
  overflow-x: auto;
  white-space: pre-wrap;
  color: #991b1b;
}

.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.error-reload-button,
.error-retry-button {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
}

.error-reload-button {
  background-color: #083860;
  color: white;
  border: 1px solid #083860;
}

.error-reload-button:hover {
  background-color: #0a4d84;
}

.error-retry-button {
  background-color: white;
  color: #374151;
  border: 1px solid #e5e7eb;
}

.error-retry-button:hover {
  background-color: #f9fafb;
}
`;
