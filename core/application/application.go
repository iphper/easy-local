package application

import (
	"os"
	"path/filepath"
	"reflect"
	"strings"
	"sync"

	"easy-local/core/config"
)

type Application struct {
	// 映射类处理
	cmdClass sync.Map
	// 映射函数处理
	cmdHandle sync.Map
}

var (
	app  *Application
	once sync.Once
	// 默认命令处理路径
	DefultCmdPath = "app/command/"
)

// Instance 应用实例
func Instance() *Application {
	once.Do(func() {
		if app == nil {
			// 创建实例
			app = new(Application)
			// 初始化
			app.Init()
		}
	})
	return app
}

// Init 应用初始化
func (app *Application) Init() {
	// 加载配置
	config.Instance()
}

// RegClass 注册类处理
func (app *Application) RegCmdClass(class Handle) *Application {

	// 获取处理类的所有公开方法
	// 反射获取方法列表
	ref := reflect.TypeOf(class)
	refv := ref

	// 如果是指针类型，获取元素类型
	if ref.Kind() == reflect.Ptr {
		refv = ref.Elem()
	}

	pkgPath := refv.PkgPath()
	// 从默认路径后面截取路径
	pkgPath = pkgPath[strings.Index(pkgPath, DefultCmdPath)+len(DefultCmdPath):]
	// 注册名称
	name := strings.ToLower(filepath.Join(pkgPath, refv.Name()))

	// 注册类处理记录
	app.cmdClass.LoadOrStore(name, class)

	// 获取基础方法【不注册基础方法】
	handleFnsRef := reflect.TypeOf((*Handle)(nil)).Elem()
	jumpMethods := map[string]struct{}{}
	for i := 0; i < handleFnsRef.NumMethod(); i++ {
		jumpMethods[handleFnsRef.Method(i).Name] = struct{}{}
	}

	// 将方法注册到函数处理列表中
	for i := 0; i < ref.NumMethod(); i++ {
		method := ref.Method(i)
		if _, ok := jumpMethods[method.Name]; ok {
			continue
		}
		app.RegCmdHandle(filepath.Join(name, strings.ToLower(method.Name)), func() {
			method.Func.Call([]reflect.Value{reflect.ValueOf(class)})
		})
	}

	return app
}

// RegHandle 注册函数处理
func (app *Application) RegCmdHandle(name string, handle HandleFn) *Application {
	// 先获取列表
	list, _ := app.cmdHandle.LoadOrStore(name, []HandleFn{})
	list = append(list.([]HandleFn), handle)
	app.cmdHandle.Store(name, list)
	return app
}

// GetClass 获取类处理
func (app *Application) GetCmdClass(name string) (Handle, bool) {
	class, ok := app.cmdClass.Load(name)
	if !ok {
		return nil, false
	}
	return class.(Handle), true
}

// GetHandle 获取函数处理列表
func (app *Application) GetCmdHandle(name string) ([]HandleFn, bool) {
	handle, ok := app.cmdHandle.Load(name)
	if !ok {
		return nil, false
	}
	return handle.([]HandleFn), true
}

// CallClass 调用类处理
func (app *Application) CallCmdClass(name string) bool {
	class, ok := app.GetCmdClass(name)
	if !ok {
		// 再去掉最后一个分隔符后面部分再试一次
		name = filepath.Dir(name)
		class, ok = app.GetCmdClass(name)
		if !ok {
			return false
		}
	}
	class.Init()
	class.Handle()
	return true
}

// CallHandle 调用所有函数处理
func (app *Application) CallCmdHandle(name string) bool {
	handles, ok := app.GetCmdHandle(name)
	if !ok {
		return false
	}
	for _, handle := range handles {
		handle()
	}
	return true
}

// Run 运行应用
func (app *Application) Run() {
	if len(os.Args) < 2 {
		return
	}

	name := os.Args[1]

	// 先判断是否存在处理函数
	if app.CallCmdHandle(name) {
		return
	}

	// 没有处理函数就判断处理类[类没有指定方法时才调用Handle]
	app.CallCmdClass(name)

}
