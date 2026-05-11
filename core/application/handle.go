package application

// HandleFn 统一处理函数类型
type HandleFn func()

// Handle 统一处理接口
type Handle interface {
	// Init 初始化
	Init()

	// Handle 处理逻辑
	Handle()
}
